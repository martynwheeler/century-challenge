<?php

namespace App\Service;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Simple PHP Library for the Komoot API
 *
 * @author Martyn Wheeler
 */

class KomootAPI
{
    public const BASE_URL = 'https://auth.komoot.de/';
    public const API_URL = 'https://external-api.komoot.de/v007/';

    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    /**
     * Makes HTTP Request to the API
     *
     * @throws \Exception
     */
    protected function request(string $token, string $url, array $query): array
    {
        try {
            //Create a new client from the komoot api
            $httpClient = HttpClient::create(['base_uri' => self::API_URL]);
            //Set up request headers
            $headers = [
                'Authorization' => 'Bearer ' . $token,
                'Content-type' => 'application/json'
            ];
            //Get response
            $response = $httpClient->request('GET', $url, [
                'headers' => $headers,
                'query' => $query,
            ]);
            $content = $response->toArray();
        } catch (\Exception $e) {
            print $e->getMessage().'<br/>';
            return $e->getResponse()->getStatusCode();
        }
        //Return the body of the response object as an array
        return $content;
    }

    /**
     * Gets a new token using the current refresh token
     *
     * @throws \Exception
     */
    public function getToken(Object $user): string
    {
        try {
            //Create a new client
            $httpClient = HttpClient::create(['base_uri' => self::BASE_URL]);

            //Get the last refresh token from the user entity
            $refreshToken = $user->getKomootRefreshToken();
            
            //Get response
            $response = $httpClient->request('POST', 'oauth/token', [
                'headers' => ['Accept' => 'application/json'],
                'auth_basic' => [$_ENV['KOMOOT_ID'], $_ENV['KOMOOT_SECRET']],
                'query' => ['refresh_token' => $refreshToken, 'grant_type' => 'refresh_token'],
            ]);

            //Grab the token from the response
            $accessToken = $response->toArray();

            //Persist the new refresh token to the db
            $user->setKomootRefreshToken($accessToken['refresh_token']);
            $user->setKomootTokenExpiry($accessToken['expires_in'] + time());
            $this->doctrine->getManager()->flush();
        } catch (\Exception $e) {
            print $e->getMessage();
            die;
        }
        return $accessToken['access_token'];
    }

    /**
     * Gets details about authenticated rider
     *
     * @throws \Exception
     */
    public function getAthlete(string $token, string $user): array
    {
        $url = 'users/' . $user;
        $athlete = $this->request($token, $url, $query = []);
        //Check for error
        if (gettype($athlete) != 'array') {
            print "HTTP Error ".$athlete.". This is not a valid Komoot ID, go back and try again.";
            die;
        }
        return $athlete;
    }

    /**
     * Gets this month's valid rides
     *
     * @throws \Exception
     */
    public function getAthleteActivitiesThisMonth(string $token, string $user): array
    {
        //Get date of first day of month
        $after = new \DateTime();
        $after->modify('midnight')->modify('first day of this month');

        //Set up request
        $url = 'users/' . $user . '/tours/';
        $query = ['start_date' => $after->format('Y-m-d\TH:i:s.u\Z')];

        //Make request and get response from API
        $athleteactivities = $this->request($token, $url, $query)["_embedded"]['tours'];

        //Process the results
        $results = [];
        if (is_array($athleteactivities) || $athleteactivities instanceof Countable) {
            foreach ($athleteactivities as $athleteactivity) {
                if ($athleteactivity['distance'] >= 100000) {
                    // Gives odd error when planned tour! Need an ignore statement
                    //echo '<pre>';
                    //var_dump($athleteactivity);
                    //echo '</pre>';
                    //convert to km and round to nearest 10m
                    $athleteactivity['distance'] = round(($athleteactivity['distance'] / 1000), 2);
                    //Correct tz to accomodate DST
                    $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $athleteactivity['date'], new \DateTimeZone('Europe/London'));
                    //key is displayed in drop down box
                    $key = "Ride {$athleteactivity['id']} on ({$date->format('d-m-Y')}) of {$athleteactivity['distance']} km";
                    $results[] = [
                        'key' => $key,
                        'id' => $athleteactivity['id'],
                        'date' => $date,
                        'distance' => $athleteactivity['distance'],
                        'average' => $athleteactivity['distance']/$athleteactivity['time_in_motion'] * 3600,
                    ];
                }
            }
        }

        //Sort and return
        usort($results, [$this, 'date_compare']);
        return $results;
    }

    /**
     * Get ride data by id
     *
     * @throws \Exception
     */
    public function getAthleteActivity(string $token, string $id): array
    {
        //Set up request
        $url = 'tours/' . $id;

        //Make request and get response from API
        $athleteactivity = $this->request($token, $url, $query = []);
        //Check for error
        if (gettype($athleteactivity) != 'array') {
            print "HTTP Error ".$athleteactivity.". This is not a valid ride ID, go back and try again.";
            die;
        }

        //Process the results and return
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $athleteactivity['date']);
        $result = [
            'date' => $date,
            'distance' => round(($athleteactivity['distance'] / 1000), 2),
            'average' => $athleteactivity['distance']/$athleteactivity['time_in_motion'] * 3.6,
        ];
        return $result;
    }

    /**
     * Check if submitted ride is a club ride
     *
     * @throws \Exception
     */
    public function isClubRide(string $token, string $id, \DateTime $date): bool
    {
        //Set up request
        $url = 'tours/' . $id . '/coordinates';

        //Make request and get response from API
        $stream_details = $this->request($token, $url, $query = []);

        //Set coordinates of start
        $bLat = 52.609323;
        $bLong = -1.261049;

        //Correct tz to accomodate DST
        $tz = new \DateTimeZone('Europe/London');

        //Must be at start between 0820 and 0900 for Saturday
        $startTime = null;
        $endTime = null;
        if ($date->format('w') == 6) {
            $startTime = \DateTime::createFromFormat('Y-m-d H:i:s', "{$date->format('Y-m-d')} 08:20:00", $tz);
            $endTime = \DateTime::createFromFormat('Y-m-d H:i:s', "{$date->format('Y-m-d')} 09:00:00", $tz);
        } elseif ($date->format('w') == 0) {
            $startTime = \DateTime::createFromFormat('Y-m-d H:i:s', "{$date->format('Y-m-d')} 08:40:00", $tz);
            $endTime = \DateTime::createFromFormat('Y-m-d H:i:s', "{$date->format('Y-m-d')} 09:30:00", $tz);
        }

        //Loop over stream to see if club ride and return
        $clubride = false;
        for ($i = 0, $l = is_countable($stream_details['items']) ? count($stream_details['items']) : 0; $i < $l; ++$i) {
            $lat = $stream_details['items'][$i]['lat'];
            $long = $stream_details['items'][$i]['lng'];
            $dist = $this->getGPXDistance($lat, $long, $bLat, $bLong);
            if ($dist < 0.05 && ($date->format('w') == 0 || $date->format('w') == 6)) {
                $tempdate = clone $date;
                $tempdate->modify('+' . $stream_details['items'][$i]['t']/1000 . 'seconds');
                if ($tempdate > $startTime && $tempdate < $endTime) {
                    $clubride = true;
                }
            }
        }
        return $clubride;
    }

    /**
     * Check if submitted ride is a club ride
     *
     * @throws \Exception
     */
    public function isRealRide(string $token, string $id): bool
    {
        //Set up request
        $url = 'tours/' . $id . '/coordinates';

        //Make request and get response from API
        $stream_details = $this->request($token, $url, $query = []);

        //Set coordinates of start
        $startLat = $stream_details['items'][0]['lat'];
        $startLong = $stream_details['items'][0]['lng'];

        //Loop over stream to check distance moved and return
        $maxdist = 0;
        for ($i = 0, $l = is_countable($stream_details['items']) ? count($stream_details['items']) : 0; $i < $l; ++$i) {
            $lat = $stream_details['items'][$i]['lat'];
            $long = $stream_details['items'][$i]['lng'];
            $dist = $this->getGPXDistance($lat, $long, $startLat, $startLong);
            if ($dist > $maxdist) {
                $maxdist = $dist;
            }
        }
        $realride = false;
        if ($maxdist > 1.0) {
            $realride = true;
        }
        return $realride;
    }

    /**
     * Get a distance between two GPS coordinates
     */
    public function getGPXDistance(float $latitude1, float $longitude1, float $latitude2, float $longitude2): float
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
    public function date_compare(array $a, array $b): int
    {
        $t1 = $a['date']->getTimestamp();
        $t2 = $b['date']->getTimestamp();
        return $t2 - $t1;
    }
}
