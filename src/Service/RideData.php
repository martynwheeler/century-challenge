<?php

namespace App\Service;

use App\Entity\Ride;
use App\Entity\User;
use DateInterval;
use Dateperiod;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use JetBrains\PhpStorm\ArrayShape;

class RideData
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    /**
     * Gets the data for rides by user or all users
     */
    #[ArrayShape(['users' => "array", 'months' => "array"])]
    public function getRideData(?string $year, ?string $username): array
    {
        //Date range to loop over
        //Can change when rides need to be entered here
        //Modified for MySQL 8 - 13/3/20
        $thisYear = false;
        if (!$year) {
            $start = new DateTime('first day of January');
            $end = new DateTime();
            $thisYear = true;
        } else {
            $start = new DateTime("$year-01-01");
            $end = new DateTime("$year-12-01");
        }
        $year = $start;
        $end->modify('midnight')->modify('last day of this month');
        $interval = new DateInterval('P1M');
        $period = new Dateperiod($start, $interval, $end);

        //Get data from repos
        $rides = $this->doctrine->getRepository(Ride::class)->findRidesByYear($year);
        $entityManager = $this->doctrine->getRepository(User::class);
        if (!$username) {
            //Get all users
            $users = $entityManager->findBy([], ['id' => 'ASC']);
        } else {
            //Get the specified user
            $users = $entityManager->findBy(['username' => $username]);
        }

        //create results array by looping over the users
        $results = [];
        for ($i = 0; $i < count($users); $i++) {
            $results[$i]['id'] = $users[$i]->getId();
            $results[$i]['name'] = $users[$i]->getName();
            $results[$i]['username'] = $users[$i]->getUserIdentifier();
            $results[$i]['privateName'] = $users[$i]->getPrivateName();
            $results[$i]['stravaUserId'] = $users[$i]->getStravaId();
            $results[$i]['komootUserId'] = $users[$i]->getKomootId();
            $results[$i]['email'] = $users[$i]->getEmail();
            $results[$i]['isDisqualified'] = false;
            $results[$i]['totalPoints'] = 0;
            $results[$i]['totalDistance'] = 0;
            $results[$i]['totalClubRides'] = 0;
            $results[$i]['months'] = [];
        }

        //Now add the monthly rides and points to the results array
        $months = [];
        foreach ($period as $month) {
            $months[] = $month->format('M');
            for ($i = 0; $i < count($results); $i++) {
                $results[$i]['months'] += [$month->format('M') => ['points' => 0, 'distance' => 0]];
                foreach ($rides as $ride) {
                    if (
                        $ride->getUser()->getID() == $results[$i]['id']
                        && $ride->getDate()->format('Y M') == $month->format('Y M')
                    ) {
                        $distance = $ride->getKm();
                        $speed = $ride->getAverageSpeed();
                        $clubRide = $ride->getClubRide();
                        $points = $this->setPoints($distance);
                        $clubRides = 0;
                        if ($clubRide) {
                            $clubRides++;
                        }
                        $results[$i]['months'][$month->format('M')]['points'] += $points;
                        $results[$i]['months'][$month->format('M')]['distance'] += $distance;
                        $results[$i]['months'][$month->format('M')]['rides'][] = [
                            'id' => $ride->getId(),
                            'km' => $distance,
                            'speed' => $speed,
                            'clubRide' => $clubRide,
                            'points' => $points,
                            'rideId' => $ride->getRideId(),
                            'source' => $ride->getSource(),
                            'date' => $ride->getDate()->format("d-m-Y"),
                        ];
                        $results[$i]['totalPoints'] += $points;
                        $results[$i]['totalDistance'] += $distance;
                        $results[$i]['totalClubRides'] += $clubRides;
                    }
                }
                if ($thisYear) {
                    if (
                        $results[$i]['months'][$month->format('M')]['points'] == 0
                        && $month->format('M') != $end->format('M')
                    ) {
                        $results[$i]['isDisqualified'] = true;
                    }
                } else {
                    if ($results[$i]['months'][$month->format('M')]['points'] == 0) {
                        $results[$i]['isDisqualified'] = true;
                    }
                }
            }
        }
        /*
        var_dump($months);
        foreach($results as $result){
            echo '<pre>' , var_dump($result) , '</pre>';
        }
        exit();
        */
        //sort the results by total points and return
        usort($results, [$this, 'sortByTotal']);
        return ['users' => $results, 'months' => $months];
    }

    /**
     * Sets the points for a ride based on its distance
     */
    protected function setPoints(float $km): int
    {
        if ($km >= 100 && $km < 150) { //For 100km, you get 10 points
            return 10;
        } elseif ($km >= 150) { //for every 50km over 100km you get a further 5 points
            //take first 100km off to work out additional points
            $km = $km - 100;

            //Could instead / 10?
            return 10 + floor($km / 50) * 5; //add
        } else { //under 100km you get 0 points.
            return 0;
        }
    }

    /**
     * Comparison function to determine the order of the riders by totalPoints
     */
    protected function sortByTotal(array $a, array $b): int
    {
        $a = $a['totalPoints'];
        $b = $b['totalPoints'];
        return $b <=> $a;
    }
}
