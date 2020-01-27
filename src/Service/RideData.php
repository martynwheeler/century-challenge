<?php

namespace App\Service;

use App\Entity\Ride;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class RideData
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    public function getRideData($year, $username = null)
    {
        //Date range to loop over
        //Can change when rides need to be entered here
        $thisyear = false;
        if (!$year) {
            $year = (new \DateTime())->format("Y");
            $end = new \DateTime();
            $thisyear = true;
        } else {
            $end = new \DateTime($year.'-12-01');
        }
        $start = new \DateTime($year.'-01-01');
        $end->modify('midnight')->modify('last day of this month');
        $interval = new \DateInterval('P1M');
        $period = new \Dateperiod($start, $interval, $end);

        //Get data from repos
        $entityManager = $this->em->getRepository(Ride::class);
        $rides = $entityManager->findRidesByYear($year);
        $entityManager = $this->em->getRepository(User::class);
        if (!$username) {
            $users = $entityManager->findBy([], ['id' => 'ASC']);
        } else {
            $users = $entityManager->findBy(['username' => $username], ['id' => 'ASC']);
        }
                
        $results = [];
        for ($i = 0; $i < count($users); $i++) {
            $results[$i]['id'] = $users[$i]->getId();
            $results[$i]['name'] = $users[$i]->getName();
            $results[$i]['username'] = $users[$i]->getUsername();
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
        usort($results, [$this, 'sortByTotal']);
        return ['users' => $results, 'months' => $months];
    }
    
    public function setPoints($km)
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

    public function sortByTotal($a, $b)
    {
        $a = $a['totalpoints'];
        $b = $b['totalpoints'];
        if ($a == $b) {
            return 0;
        }
        return ($a < $b) ? 1 : -1;
    }
}