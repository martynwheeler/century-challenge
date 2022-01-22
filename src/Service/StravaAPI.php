<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Simple PHP Library for the Stava API
 *
 * @author Martyn Wheeler
 */

class StravaAPI
{
    public const API_URL = 'https://www.strava.com/api/v3/';

    public function __construct(private EntityManagerInterface $em, private RouterInterface $router) {}

    /**
     * Deauthorize the app from strava
     */
    public function deauthorize(string $accessToken): array
    {
        //Create a new client
        $httpClient = HttpClient::create(['base_uri' => self::API_URL]);
        $response = $httpClient->request('POST', 'oauth/deauthorize', [
            'query' => ['access_token' => $accessToken],
        ]);
        return $response->toArray(false);
    }

    /**
     * Makes HTTP Request to the API
     */
    protected function request(?string $token, string $url, array $query): array
    {
        //Create a new client
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
        //Return the body of the response object as an array
        return $response->toArray(false);
    }

    /**
     * Gets a new token using the current refresh token
     */
    public function getToken(Object $user): ?string
    {
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
        //If the request is granted, persist the new refresh token to the db
        if (!array_key_exists('errors', $accessToken)) {
            //get an error
            $user->setStravaRefreshToken($accessToken['refresh_token']);
            $user->setStravaTokenExpiry($accessToken['expires_at']);
            $entityManager = $this->em;
            $entityManager->persist($user);
            $entityManager->flush();
            return $accessToken['access_token'];
        }
        // if the refresh token is invalid return null
        return null;
    }

    /**
     * Gets details about authenticated rider
     *
     * @throws \Exception
     */
    public function getAthlete(?string $token): array
    {
        $url = 'athlete';
        $athlete = $this->request($token, $url, $query = []);
        //Check for error
        if (gettype($athlete) != 'array') {
            print "HTTP Error ".$athlete.". This is not a valid Strava ID, go back and try again.";
            die;
        }
        return $athlete;
    }

    /**
     * Gets this month's valid rides
     *
     * @throws \Exception
     */
    public function getAthleteActivitiesThisMonth(string $token): array
    {
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
                if ($athleteactivity['distance'] >= 100000) {
                    //convert to km and round to nearest 10m
                    $athleteactivity['distance'] = round(($athleteactivity['distance'] / 1000), 2);
                    //key is displayed in drop down box
                    $key = 'Ride '.$athleteactivity['id'].' on ('.
                        \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $athleteactivity['start_date'])->format('d-m-Y').') of '.
                        $athleteactivity['distance'].' km';
                    $results[] = [
                        'key' => $key,
                        'id' => $athleteactivity['id'],
                        'date' => \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $athleteactivity['start_date']),
                        'distance' => $athleteactivity['distance'],
                        'average' => $athleteactivity['average_speed'] * 3.6,
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
        $url = 'activities/'. $id;

        //Make request and get response from API
        $athleteactivity = $this->request($token, $url, $query = []);
        //Check for error
        if (gettype($athleteactivity) != 'array') {
            print "HTTP Error ".$athleteactivity.". This is not a valid ride ID, go back and try again.";
            die;
        }

        //Process the results and return
        $result = [
            'date' => \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $athleteactivity['start_date']),
            'distance' => round(($athleteactivity['distance'] / 1000), 2),
            'average' => $athleteactivity['average_speed'] * 3.6,
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
        $url = 'activities/'. $id . '/streams/latlng';
        $query = ['resolution' => null, 'series_type' => 'time'];

        //Make request and get response from API
        $stream_details = $this->request($token, $url, $query);

        //Set coordinates of start
        $bLat = 52.609323;
        $bLong = -1.261049;

        //Correct tz to accomodate DST
        $tz = new \DateTimeZone('Europe/London');
        $date->setTimezone($tz);

        //Must be at start between 0820 and 0900
        $startTime = \DateTime::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d')." 08:20:00", $tz);
        $endTime = \DateTime::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d')." 09:00:00", $tz);

        //Loop over stream to see if club ride and return
        $times = $stream_details[1]['data'];
        $clubride = false;
        for ($i = 0, $l = is_countable($stream_details[0]['data']) ? count($stream_details[0]['data']) : 0; $i < $l; ++$i) {
            $lat = $stream_details[0]['data'][$i][0];
            $long = $stream_details[0]['data'][$i][1];
            $dist = $this->getGPXDistance($lat, $long, $bLat, $bLong);
            if ($dist < 0.05 && $date->format('w') == 6) {
                $tempdate = clone $date;
                $tempdate->modify('+' . $times[$i] . 'seconds');
                if ($tempdate > $startTime && $tempdate < $endTime) {
                    $clubride = true;
                }
            }
        }
        return $clubride;
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
