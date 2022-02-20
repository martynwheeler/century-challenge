<?php

namespace App\Service;

use App\Entity\Ride;
use App\Entity\User;
use DateTime;
use DateTimeZone;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Simple PHP Library for the Komoot API
 *
 * @author Martyn Wheeler
 */

class KomootAPI
{
    protected const BASE_URL = 'https://auth-api.main.komoot.net/';
    protected const API_URL = 'https://external-api.komoot.de/v007/';

    public function __construct(private ManagerRegistry $doctrine, private RequestStack $requestStack)
    {
    }

    /**
     * Deauthorize the app from komoot
     * (https://static.komoot.de/doc/auth/oauth2.html#v1_clients__client_id__refresh_tokens)
     * @throws TransportExceptionInterface
     */
    public function deauthorize(User $user): ?int
    {
        if ($user->getKomootID() && $user->getKomootRefreshToken()) {
            //Create a new client
            $httpClient = HttpClient::create(['base_uri' => self::BASE_URL]);

            //Get response
            $response = $httpClient->request('DELETE', "v1/clients/{$_ENV['KOMOOT_ID']}/refresh_tokens/", [
                'headers' => ['Accept' => 'application/json'],
                'auth_basic' => [$_ENV['KOMOOT_ID'], $_ENV['KOMOOT_SECRET']],
                'query' => ['refresh_token' => $user->getKomootRefreshToken()],
            ]);

            //Return the status code of the response object
            return $response->getStatusCode();
        }

        return null;
    }

    /**
     * Gets a new token using the current refresh token
     * @throws TransportExceptionInterface
     */
    protected function getToken(User $user): ?string
    {
        if ($user->getKomootID() && $user->getKomootRefreshToken()) {
            //get the current session
            $session = $this->requestStack->getSession();

            //check for short-lived access token in session and whether it has expired
            if (!$session->get('komoot.token') || $user->getKomootTokenExpiry() - time() < 30) {
                //Create a new client
                $httpClient = HttpClient::create(['base_uri' => self::BASE_URL]);

                //Get the last refresh token from the user entity
                $refreshToken = $user->getKomootRefreshToken();

                //Get response from server
                $response = $httpClient->request('POST', 'oauth/token', [
                    'headers' => ['Accept' => 'application/json'],
                    'auth_basic' => [$_ENV['KOMOOT_ID'], $_ENV['KOMOOT_SECRET']],
                    'query' => ['refresh_token' => $refreshToken, 'grant_type' => 'refresh_token'],
                ]);

                //Grab the token from the response
                $accessToken = $response->toArray(false);

                //If request is granted, add the new refresh token to the db and add short-lived token to the session
                if (!array_key_exists('error', $accessToken)) {
                    $user->setKomootRefreshToken($accessToken['refresh_token']);
                    $user->setKomootTokenExpiry($accessToken['expires_in'] + time());
                    $this->doctrine->getManager()->flush();
                    $session->set('komoot.token', $accessToken['access_token']);
                    return $accessToken['access_token'];
                }

                //If the refresh token response is invalid return null
                return null;
            }

            //short-lived access token already in session and valid
            return $session->get('komoot.token');
        }

        //The user is not connected to komoot
        return null;
    }

    /**
     * Makes an HTTP Request to the API to return athlete data (see komoot API reference for query parameters)
     * @throws TransportExceptionInterface
     */
    protected function request(?string $token, string $url, array $query): array
    {
        //Create a new client from the komoot api
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
     * @throws TransportExceptionInterface
     */
    public function getAthlete(User $user): ?array
    {
        //get an access token and return athlete details
        $token = $this->getToken($user);
        if ($token) {
            //Set up request
            $url = "users/{$user->getKomootID()}";

            //request athlete data from API - check after the function call for errors
            return $this->request($token, $url, []);
        }

        return null;
    }

    /**
     * Get data for this month's valid rides
     * @throws TransportExceptionInterface
     */
    public function getAthleteActivitiesThisMonth(User $user): ?array
    {
        //get an access token and return athlete rides
        $token = $this->getToken($user);
        if ($token) {
            //Get date of first day of month
            $after = new DateTime();
            $after->modify('midnight')->modify('first day of this month');

            //Set up request
            $url = "users/{$user->getKomootID()}/tours/";
            $query = ['start_date' => $after->format('Y-m-d\TH:i:s.u\Z')];

            //Make request and get response from API
            $athleteActivities = $this->request($token, $url, $query)["_embedded"]['tours'];

            //Process the results
            $rides = [];
            foreach ($athleteActivities as $athleteActivity) {
                $ride = $this->setRide($athleteActivity);
                if ($ride) {
                    $rides[] = $ride;
                }
            }

            //Sort and return
            usort($rides, [$this, 'rideDateCompare']);
            return $rides;
        }

        return null;
    }

    /**
     * Get data for a valid ride by id
     * @throws TransportExceptionInterface
     */
    public function getAthleteActivity(User $user, string $id): ?Ride
    {
        //get an access token and return ride analysis
        $token = $this->getToken($user);
        if ($token) {
            //Set up request
            $url = "tours/$id";

            //Make request and get response from API
            $athleteActivity = $this->request($token, $url, []);

            //Check for error and return null if any errors found
            if (array_key_exists('error', $athleteActivity)) {
                return null;
            }

            //Process the results and return
            $ride = $this->setRide($athleteActivity);
            $ride->setUser($user);
            $checkRideStream = $this->processRideStream($user, $id, $ride->getDate());
            $ride->setClubRide($checkRideStream['isClubRide']);
            if (!$checkRideStream['isRealRide']) {
                $ride = null;
            }
            return $ride;
        }

        return null;
    }

    /**
     * Check if submitted ride is a real/club ride
     * @throws TransportExceptionInterface
     */
    public function processRideStream(User $user, string $id, DateTime $date): ?array
    {
        //get an access token and return ride analysis
        $token = $this->getToken($user);
        if ($token) {
            //Set up request
            $url = "tours/$id/coordinates";

            //Make request and get response from API
            $stream_details = $this->request($token, $url, []);

            //Set coordinates of start
            $startLat = $stream_details['items'][0]['lat'];
            $startLong = $stream_details['items'][0]['lng'];

            //Set coordinates of Bull's Head
            $bLat = 52.609323;
            $bLong = -1.261049;

            //Correct tz to accommodate DST
            $tz = new DateTimeZone('Europe/London');

            //Must be at start between 0820 and 0900 for Saturday
            $startTime = null;
            $endTime = null;
            if ($date->format('w') == 6) {
                $startTime = DateTime::createFromFormat('Y-m-d H:i:s', "{$date->format('Y-m-d')} 08:20:00", $tz);
                $endTime = DateTime::createFromFormat('Y-m-d H:i:s', "{$date->format('Y-m-d')} 09:00:00", $tz);
            } elseif ($date->format('w') == 0) {
                $startTime = DateTime::createFromFormat('Y-m-d H:i:s', "{$date->format('Y-m-d')} 08:40:00", $tz);
                $endTime = DateTime::createFromFormat('Y-m-d H:i:s', "{$date->format('Y-m-d')} 09:30:00", $tz);
            }

            //Loop over stream to see if club ride and to check distance moved and return
            $maxDistance = 0;
            $clubRide = false;
            for (
                $i = 0,
                $l = is_countable($stream_details['items']) ? count($stream_details['items']) : 0;
                $i < $l; ++$i
            ) {
                $lat = $stream_details['items'][$i]['lat'];
                $long = $stream_details['items'][$i]['lng'];

                //Calc distance to BH
                $distToBH = $this->getGPXDistance($lat, $long, $bLat, $bLong);

                //If close then check times
                if ($distToBH < 0.05 && ($date->format('w') == 0 || $date->format('w') == 6)) {
                    $tempDate = clone $date;
                    $tempDate->modify('+' . $stream_details['items'][$i]['t'] / 1000 . 'seconds');
                    if ($tempDate > $startTime && $tempDate < $endTime) {
                        $clubRide = true;
                    }
                }

                //Calc total distance from start
                $distTotal = $this->getGPXDistance($lat, $long, $startLat, $startLong);
                if ($distTotal > $maxDistance) {
                    $maxDistance = $distTotal;
                }
            }

            //Check if the rider has actually moved
            $realRide = false;
            if ($maxDistance > 1.0) {
                $realRide = true;
            }

            return [
                "isClubRide" => $clubRide,
                "isRealRide" => $realRide,
            ];
        }

        return null;
    }

    /**
     * process the result of an athlete activity returned from strava into an array
     */
    protected function setRide(array $athleteActivity): ?Ride
    {
        $ride = null;
        if ($athleteActivity['distance'] >= 100000) {
            //convert to km and round to nearest 10m
            $athleteActivity['distance'] = round(($athleteActivity['distance'] / 1000), 2);

            //Correct tz to accommodate DST
            $date = DateTime::createFromFormat(
                'Y-m-d\TH:i:s.u\Z',
                $athleteActivity['date'],
                new DateTimeZone('Europe/London')
            );

            //Return a Ride object
            $ride = (new Ride())
                ->setRideId($athleteActivity['id'])
                ->setKm($athleteActivity['distance'])
                ->setAverageSpeed($athleteActivity['distance'] / $athleteActivity['time_in_motion'] * 3600)
                ->setDate($date)
                ->setSource('komoot');
        }

        return $ride;
    }

    /**
     * Get a distance between two GPS coordinates
     */
    protected function getGPXDistance(float $latitude1, float $longitude1, float $latitude2, float $longitude2): float
    {
        $earth_radius = 6371;
        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * asin(sqrt($a));
        return $earth_radius * $c;
    }

    /**
     * Compare two date stamps
     */
    protected function rideDateCompare(Ride $a, Ride $b): int
    {
        $t1 = $a->getDate()->getTimestamp();
        $t2 = $b->getDate()->getTimestamp();
        return $t2 - $t1;
    }
}
