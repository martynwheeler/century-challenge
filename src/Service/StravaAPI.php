<?php

namespace App\Service;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Simple PHP Library for the Stava API
 *
 * @author Martyn Wheeler
 */

class StravaAPI
{
    protected const API_URL = 'https://www.strava.com/api/v3/';

    public function __construct(private ManagerRegistry $doctrine, private RequestStack $requestStack)
    {
    }

    /**
     * Deauthorize the app from strava
     */
    public function deauthorize(Object $user): ?array
    {
        //get an access token and return athelete details
        $token = $this->getToken($user);
        if ($token){
            //Create a new client
            $httpClient = HttpClient::create(['base_uri' => self::API_URL]);

            //Get response
            $response = $httpClient->request('POST', 'oauth/deauthorize', [
                'query' => ['access_token' => $token],
            ]);

            //Return the body of the response object as an array
            return $response->toArray(false);
        }

        return null;
    }

    /**
     * Gets a new token using the current refresh token
     */
    protected function getToken(Object $user): ?string
    {
        if ($user->getStravaID() && $user->getStravaRefreshToken()) {
            //get the current request object
            $sessionExist = $this->requestStack->getCurrentRequest();

            //workaround allow call from messenger which sets $sessionExist is null
            $getNewToken = false;
            if ($sessionExist){
                //get the current user session
                $session = $this->requestStack->getSession();

                //check for short-lived access token in session and whether it has expired 
                if (!$session->get('strava.token') || $user->getStravaTokenExpiry() - time() < 30) {
                    $getNewToken = true;
                }                
            } else {
                //no session found, need to get a token
                $getNewToken = true;
            }

            if ($getNewToken) {
                //Create a new client
                $httpClient = HttpClient::create(['base_uri' => self::API_URL]);

                //Get the last refresh token from the user entity
                $refreshToken = $user->getStravaRefreshToken();

                //Get response
                $response = $httpClient->request('POST', 'oauth/token', [
                    'body' => [
                        'client_id' => $_ENV['STRAVA_ID'],
                        'client_secret' => $_ENV['STRAVA_SECRET'],
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $refreshToken,
                    ]
                ]);

                //Grab the token from the response
                $accessToken = $response->toArray(false);

                //If the request is granted, persist the new refresh token to the db and add short-lived token to the session
                if (!array_key_exists('errors', $accessToken)) {
                    $user->setStravaRefreshToken($accessToken['refresh_token']);
                    $user->setStravaTokenExpiry($accessToken['expires_at']);
                    $this->doctrine->getManager()->flush();
                    if ($sessionExist){
                        $session->set('strava.token', $accessToken['access_token']);
                    }                            
                    return $accessToken['access_token'];
                }

                //If the refresh token respone is invalid return null
                return null;
            }

            //short-lived access token already in session and valid
            return $session->get('strava.token');
        }

        //The user is not connected to komoot
        return null;
    }

    /**
     * Makes an HTTP Request to the API to return athlete data (see strava API reference for query parameters)
     */
    protected function request(?string $token, string $url, array $query): array
    {
        //Create a new client
        $httpClient = HttpClient::create(['base_uri' => self::API_URL]);

        //Set up request headers
        $headers = [
            'Authorization' => "Bearer $token",
            'Content-type' => 'application/json'
        ];

        //Get response
        $response = $httpClient->request('GET', $url, [
            'headers' => $headers,
            'query' => $query,
        ]);

        //Return the body of the response object as an array
        return $response->toArray(false);
    }

    /**
     * Gets details about authenticated rider
     */
    public function getAthlete(Object $user): ?array
    {
        //get an access token and return athelete details
        $token = $this->getToken($user);
        if ($token){        
            //Set up request
            $url = 'athlete';

            //request athelete data from API - check after the function call for errors
            return $this->request($token, $url, $query = []);
        }

        return null;
    }

    /**
     * Get data for this month's valid rides
     */
    public function getAthleteActivitiesThisMonth(Object $user): ?array
    {
        //get an access token and return athelete rides
        $token = $this->getToken($user);
        if ($token){
            //Get date of first day of month
            $after = new \DateTime();
            $after->modify('midnight')->modify('first day of this month');

            //Set up request
            $url = 'athlete/activities';
            $query = ['before' => null, 'after' => $after->getTimestamp(), 'page' => null, 'per_page' => 100];

            //Make request and get response from API
            $athleteactivities = $this->request($token, $url, $query);

            //Process the results
            $results = [];
            if (is_array($athleteactivities) || $athleteactivities instanceof Countable) {
                foreach ($athleteactivities as $athleteactivity) {
                    $result = $this->setResult($athleteactivity);
                    if ($result) {
                        $results[] = $result;
                    }
                }
            }

            //Sort and return
            usort($results, [$this, 'date_compare']);
            return $results;
        }

        return null;
    }

    /**
     * Get data for a valid ride by id
     */
    public function getAthleteActivity(Object $user, string $id): ?array
    {
        //get an access token and return ride analysis
        $token = $this->getToken($user);
        if ($token){
            //Set up request
            $url = "activities/$id";

            //Make request and get response from API
            $athleteactivity = $this->request($token, $url, $query = []);

            //Check for error and return null if any errors found
            if (array_key_exists('errors', $athleteactivity)) {
                return null;
            }

            //Process the results and return
            $result = $this->setResult($athleteactivity);
            $checkRideStream = $this->processRideStream($user, $id, $result['date']);
            $result['isClubride'] = $checkRideStream['isClubride'];
            $result['isRealride'] = $checkRideStream['isRealride'];

            return $result;
        }

        return null;
    }

    /**
     * Check if submitted ride is a real/club ride
     */
    public function processRideStream(Object $user, string $id, \DateTime $date): ?array
    {
        //get an access token and return ride analysis
        $token = $this->getToken($user);
        if ($token){
            //Set up request
            $url = "activities/$id/streams/latlng";
            $query = ['resolution' => null, 'series_type' => 'time'];

            //Make request and get response from API
            $stream_details = $this->request($token, $url, $query);

            //Set coordinates of ride start
            $startLat = $stream_details[0]['data'][0][0];
            $startLong = $stream_details[0]['data'][0][1];

            //Set coordinates of Bull's Head
            $bLat = 52.609323;
            $bLong = -1.261049;

            //Correct tz to accomodate DST
            $tz = new \DateTimeZone('Europe/London');

            //Must be at start between 0820 and 0900 for Saturday
            $startTime = null;
            $endTime = null;
            if ($date->format('w') == 6) { //Saturday
                $startTime = \DateTime::createFromFormat('Y-m-d H:i:s', "{$date->format('Y-m-d')} 08:20:00", $tz);
                $endTime = \DateTime::createFromFormat('Y-m-d H:i:s', "{$date->format('Y-m-d')} 09:00:00", $tz);
            } elseif ($date->format('w') == 0) { //Saturday
                $startTime = \DateTime::createFromFormat('Y-m-d H:i:s', "{$date->format('Y-m-d')} 08:40:00", $tz);
                $endTime = \DateTime::createFromFormat('Y-m-d H:i:s', "{$date->format('Y-m-d')} 09:30:00", $tz);
            }

            //Loop over stream to see if club ride and to check distance moved and return
            $times = $stream_details[1]['data'];
            $maxdist = 0;
            $clubride = false;
            for ($i = 0, $l = is_countable($stream_details[0]['data']) ? count($stream_details[0]['data']) : 0; $i < $l; ++$i) {
                $lat = $stream_details[0]['data'][$i][0];
                $long = $stream_details[0]['data'][$i][1];

                //Calc distance to BH
                $distToBH = $this->getGPXDistance($lat, $long, $bLat, $bLong);

                //If close then check times
                if ($distToBH < 0.05 && ($date->format('w') == 0 || $date->format('w') == 6)) {
                    $tempdate = clone $date;
                    $tempdate->modify('+' . $times[$i] . 'seconds');
                    if ($tempdate > $startTime && $tempdate < $endTime) {
                        $clubride = true;
                    }
                }

                //Calc total distance from start
                $distTotal = $this->getGPXDistance($lat, $long, $startLat, $startLong);
                if ($distTotal > $maxdist) {
                    $maxdist = $distTotal;
                }
            }

            //Check if the rider has actually moved
            $realride = false;
            if ($maxdist > 1.0) {
                $realride = true;
            }

            return [
                "isClubride" => $clubride,
                "isRealride" => $realride,
            ];
        }

        return null;
    }

    /**
     * process the result of an athelete activity returned from strava into an array
     */
    protected function setResult(array $athleteactivity): ?array
    {
        $result = null;
        if ($athleteactivity['distance'] >= 100000) {
            //convert to km and round to nearest 10m
            $athleteactivity['distance'] = round(($athleteactivity['distance'] / 1000), 2);
            //Correct tz to accomodate DST
            $date = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $athleteactivity['start_date'], new \DateTimeZone('Europe/London'));
            //generate key for display in drop down box
            $key = "Ride {$athleteactivity['id']} on ({$date->format('d-m-Y')}) of {$athleteactivity['distance']} km";
            //checking for club ride/real ride not done here as it would result in too many additional API calls
            $result = [
                'key' => $key,
                'id' => $athleteactivity['id'],
                'date' => $date,
                'distance' => $athleteactivity['distance'],
                'average' => $athleteactivity['average_speed'] * 3.6,
            ];
        }
        return $result;
    }

    /**
     * Get a distance between two GPS coordinates
     */
    protected function getGPXDistance(float $latitude1, float $longitude1, float $latitude2, float $longitude2): float
    {
        $earth_radius = 6371;
        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;
        return $d;
    }

    /**
     * Compare two date stamps
     */
    protected function date_compare(array $a, array $b): int
    {
        $t1 = $a['date']->getTimestamp();
        $t2 = $b['date']->getTimestamp();
        return $t2 - $t1;
    }
}
