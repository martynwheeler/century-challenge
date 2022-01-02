<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Simple PHP Library for the Komoot API
 *
 * @author Martyn Wheeler
 */

class KomootAPI
{
    const BASE_URL = 'https://auth.komoot.de/';
    const API_URL = 'https://external-api.komoot.de/v007/';

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Makes HTTP Request to the API
     *
     * @param string $token
     * @param string $url
     * @param array $query
     *
     * @return mixed
     * @throws \Exception
     */
    protected function request($token, $url, $query = [])
    {
        try {
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
     * @param string $refreshToken
     *
     * @return string
     * @throws \Exception
     */
    public function getToken($user)
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
            $entityManager = $this->em;
            $entityManager->persist($user);
            $entityManager->flush();
        } catch (\Exception $e) {
            print $e->getMessage();
            die;
        }
        return $accessToken['access_token'];
    }

    /**
     * Gets details about authenticated rider
     *
     * @param string $token
     * @param string $user
     *
     * @return array
     * @throws \Exception
     */
    public function getAthlete($token, $user)
    {
        $url = 'users/' . $user;
        $athlete = $this->request($token, $url);
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
     * @param string $token
     * @param string $user
     *
     * @return array
     * @throws \Exception
     */
    public function getAthleteActivitiesThisMonth($token, $user)
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
                    //key is displayed in drop down box
                    $key = 'Ride '.$athleteactivity['id'].' on ('.
                        \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $athleteactivity['date'])->format('d-m-Y').') of '.
                        $athleteactivity['distance'].' km';
                    $results[] = [
                        'key' => $key,
                        'id' => $athleteactivity['id'],
                        'date' => \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $athleteactivity['date']),
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
     * @param string $token
     * @param string $id
     *
     * @return array
     * @throws \Exception
     */
    public function getAthleteActivity($token, $id)
    {
        //Set up request
        $url = 'tours/' . $id;

        //Make request and get response from API
        $athleteactivity = $this->request($token, $url);
        //Check for error
        if (gettype($athleteactivity) != 'array') {
            print "HTTP Error ".$athleteactivity.". This is not a valid ride ID, go back and try again.";
            die;
        }

        //Process the results and return
        $result = [
            'date' => \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $athleteactivity['date']),
            'distance' => round(($athleteactivity['distance'] / 1000), 2),
            'average' => $athleteactivity['distance']/$athleteactivity['time_in_motion'] * 3.6,
        ];
        return $result;
    }

    /**
     * Check if submitted ride is a club ride
     *
     * @param string $token
     * @param string $id
     * @param string $date
     *
     * @return boolean
     * @throws \Exception
     */
    public function isClubRide($token, $id, $date)
    {
        //Set up request
        $url = 'tours/' . $id . '/coordinates';

        //Make request and get response from API
        $stream_details = $this->request($token, $url);

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
        $clubride = false;
        for ($i = 0, $l = count($stream_details['items']); $i < $l; ++$i) {
            $lat = $stream_details['items'][$i]['lat'];
            $long = $stream_details['items'][$i]['lng'];
            $dist = $this->getGPXDistance($lat, $long, $bLat, $bLong);
            if ($dist < 0.05 && $date->format('w') == 6) {
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
     * Get a distance between two GPS coordinates
     *
     * @param float $latitude1
     * @param float $longitude1
     * @param float $latitude2
     * @param float $longitude2
     *
     * @return float
     */
    public function getGPXDistance($latitude1, $longitude1, $latitude2, $longitude2)
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
     *
     * @param date $a
     * @param date $b
     *
     * @return date
     */
    public function date_compare($a, $b)
    {
        $t1 = $a['date']->getTimestamp();
        $t2 = $b['date']->getTimestamp();
        return $t2 - $t1;
    }
}
