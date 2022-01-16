<?php

namespace App\Service;

use App\Entity\Ride;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class RideData
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Gets the data for rides by user or all users
     */
    public function getRideData(?string $year, ?string $username): array
    {
        //Date range to loop over
        //Can change when rides need to be entered here
        //Modified for MySQL 8 - 13/3/20
        $thisyear = false;
        if (!$year) {
            $start = new \DateTime('first day of January');
            $end = new \DateTime();
            $thisyear = true;
        } else {
            $start = new \DateTime($year.'-01-01');
            $end = new \DateTime($year.'-12-01');
        }
        $year = $start;
        $end->modify('midnight')->modify('last day of this month');
        $interval = new \DateInterval('P1M');
        $period = new \Dateperiod($start, $interval, $end);

        //Get data from repos
        $entityManager = $this->em->getRepository(Ride::class);
        $rides = $entityManager->findRidesByYear($year);
        $entityManager = $this->em->getRepository(User::class);
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
            $results[$i]['privatename'] = $users[$i]->getPrivateName();
            $results[$i]['stravauserid'] = $users[$i]->getStravaId();
            $results[$i]['komootuserid'] = $users[$i]->getKomootId();
            $results[$i]['email'] = $users[$i]->getEmail();
            $results[$i]['isDisqualified'] = false;
            $results[$i]['totalpoints'] = 0;
            $results[$i]['totaldistance'] = 0;
            $results[$i]['totalclubrides'] = 0;
            $results[$i]['months'] = [];
        }

        //Now add the monthly rides and points to the results array
        $months = [];
        foreach ($period as $month) {
            $months[] = $month->format('M');
            for ($i = 0; $i < count($results); $i++) {
                $results[$i]['months'] += [$month->format('M') => ['points' => 0, 'distance' => 0]];
                foreach ($rides as $ride) {
                    if ($ride->getUser()->getID() == $results[$i]['id'] && $ride->getDate()->format('Y M') == $month->format('Y M')) {
                        $distance = $ride->getKm();
                        $points = $this->setPoints($distance);
                        $clubRides = 0;
                        if ($ride->getClubRide()) {
                            $clubRides++;
                        }
                        $results[$i]['months'][$month->format('M')]['points'] += $points;
                        $results[$i]['months'][$month->format('M')]['distance'] += $distance;
                        $results[$i]['months'][$month->format('M')]['rides'][] = [
                            'id' => $ride->getId(),
                            'km' => $distance,
                            'points' => $points,
                            'rideid' => $ride->getRideId(),
                            'source' => $ride->getSource(),
                            'date' => $ride->getDate()->format("d-m-Y"),
                        ];
                        $results[$i]['totalpoints'] += $points;
                        $results[$i]['totaldistance'] += $distance;
                        $results[$i]['totalclubrides'] += $clubRides;
                    }
                }
                if ($thisyear) {
                    if ($results[$i]['months'][$month->format('M')]['points'] == 0 && $month->format('M') != $end->format('M')) {
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
        //sort the resulst by total points and return
        usort($results, [$this, 'sortByTotal']);
        return ['users' => $results, 'months' => $months];
    }

    /**
     * Sets the points for a ride based on its distance
     */
    public function setPoints(float $km): int
    {
        //For 100km, you get 10 points
        if ($km >= 100 && $km < 150) {
            return 10;
        }
        //for every 50km over 100km you get a further 5 points
        elseif ($km >= 150) {
            //take first 100km off to work out additional points
            $km = $km - 100;

            //Could instead / 10?
           return 10 + floor($km/50) * 5; //add
        }
        //under 100km you get 0 points.
        else {
            return 0;
        }
    }

    /**
     * Comparison function to determine the order of the riders by totalpoints
     */
    public function sortByTotal(array $a, array $b): int
    {
        $a = $a['totalpoints'];
        $b = $b['totalpoints'];
        return $b <=> $a;
    }
}
