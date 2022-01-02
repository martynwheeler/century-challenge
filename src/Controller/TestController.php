<?php

namespace App\Controller;

use App\Service\StravaAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    /**
     * @Route("/test", name="test")
     */
    public function index(Request $request, StravaAPI $strava_api)
    {
        //Get the user and set up ride object
        $user = $this->getUser();

        //Check if the user registered with strava
        if ($user->getStravaID() && $user->getStravaRefreshToken()) {
            //Get or refresh token as necessary
            if (!$request->getSession()->get('strava.token') || $user->getStravaTokenExpiry() - time() < 300) {
                $accessToken = $strava_api->getToken($user);
                $request->getSession()->set('strava.token', $accessToken);
            }
            $token = $request->getSession()->get('strava.token');
            $athlete = $strava_api->getAthlete($token);
            $athleteName = $athlete['firstname'].' '.$athlete['lastname'];
            $segment = $strava_api->getSegment($token, '4590237');
            echo $athleteName;
            var_dump($segment);
            exit();
        }
        

/*
        $gpx1 = simplexml_load_file('resources/Four_Peak.gpx');
        $gpx2 = simplexml_load_file('resources/Afternoon_Ride.gpx');
        $track1 = $gpx1->trk->trkseg->children();
        $track2 = $gpx2->trk->trkseg->children();
        $last = count($gpx1->trk->trkseg->children()) - 1;
        echo ($last);
        echo '<br/>';

        foreach($track2 as $point)
        {
            $dist = $this->getGPXDistance(
                current($track1[$last]['lat']),
                current($track1[$last]['lon']),
                current($point['lat']),
                current($point['lon'])
            );
            if($dist < 0.040)
            {
                echo $dist;
                echo '<br/>';
            }
        }

*/
        return $this->render('test/index.html.twig', [
            'controller_name' => 'TestController',
//            'track' => $track1,
        ]);
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

}
