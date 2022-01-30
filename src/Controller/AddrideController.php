<?php

namespace App\Controller;

use App\Entity\Ride;
use App\Form\AddrideFormType;
use App\Service\StravaAPI;
use App\Service\KomootAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class AddrideController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    #[Route('/addride', name: 'addride')]
    public function addRide(Request $request, StravaAPI $strava_api, KomootAPI $komoot_api): Response
    {
        //Get the user and set up ride object
        $user = $this->getUser();
        $ride = new Ride();
        $ride->setUser($user);
        $athleteActivities = null;

        //Switch depending on provider
        switch ($user->getPreferredProvider()) {
            case 'komoot':
                //Check if the user registered with komoot
                if ($user->getKomootID() && $user->getKomootRefreshToken()) {
                    //Get or refresh token as necessary
                    if (!$request->getSession()->get('komoot.token') || $user->getKomootTokenExpiry() - time() < 300) {
                        $accessToken = $komoot_api->getToken($user);
                        $request->getSession()->set('komoot.token', $accessToken);
                    }
                    $token = $request->getSession()->get('komoot.token');
                    $athlete = $komoot_api->getAthlete($token, $user->getKomootID());
                    $athleteName = $athlete['display_name'];
                    $athleteActivities = $komoot_api->getAthleteActivitiesThisMonth($token, $user->getKomootID());
                }
                break;
            case 'strava':
                //Check if the user registered with strava
                if ($user->getStravaID() && $user->getStravaRefreshToken()) {
                    //Get or refresh token as necessary
                    if (!$request->getSession()->get('strava.token') || $user->getStravaTokenExpiry() - time() < 3) {
                        $accessToken = $strava_api->getToken($user);
                        $request->getSession()->set('strava.token', $accessToken);
                    }
                    $token = $request->getSession()->get('strava.token');
                    $athlete = $strava_api->getAthlete($token);
                    // check for errors and redirect
                    if (array_key_exists('errors', $athlete)) {
                        $request->getSession()->set('reconnect.strava', true);
                        return $this->redirectToRoute('connect_strava');
                    }
                    //token valid, grab the details
                    $athleteName = $athlete['firstname'].' '.$athlete['lastname'];
                    $athleteActivities = $strava_api->getAthleteActivitiesThisMonth($token);
                }
                break;
            default:
                return $this->redirectToRoute('addridemanual');
        }

        if ($athleteActivities) {
            $form = $this->createFormBuilder($ride)
                ->add('ride_id', ChoiceType::class, [
                    'choices' => array_column($athleteActivities, 'id', 'key'),
                    'label' => 'Select a recent century ride from the dropdown menu:',
                    'expanded' => false,
                    'multiple' => false,
                ])
                ->getForm()
            ;
        } else {
            return $this->redirectToRoute('addridemanual');
        }

        $ride->setSource($user->getPreferredProvider());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $rideID = $form->getData()->getRideId();
            $realRide = null;
            foreach ($athleteActivities as $athleteActivity) {
                if ($athleteActivity['id'] == $rideID) {
                    $ride->setKm($athleteActivity['distance']);
                    $ride->setAverageSpeed($athleteActivity['average']);
                    $ride->setDate($athleteActivity['date']);
                    switch ($user->getPreferredProvider()) {
                        case 'komoot':
                            $ride->setClubRide($komoot_api->isClubRide($token, $rideID, $athleteActivity['date']));
                            $realRide = $komoot_api->isRealRide($token, $rideID);
                            break;
                        case 'strava':
                            $ride->setClubRide($strava_api->isClubRide($token, $rideID, $athleteActivity['date']));
                            $realRide = $strava_api->isRealRide($token, $rideID);
                            break;
                    }
                }
            }
            if ($realRide) {
                //Maybe do some error checking here?
                $entityManager = $this->doctrine->getManager();
                $entityManager->persist($ride);
                $entityManager->flush();

                // do anything else you need here, like send an email
                $this->addFlash('success', $this->getUser()->getName().', you have sucessfully added your ride');
            } else {
                $this->addFlash('warning', $this->getUser()->getName().', you cannot upload virtual rides');
            }

            return $this->redirectToRoute('displayrides', ['username' => $this->getUser()->getUserIdentifier()]);
        }

        return $this->renderForm('modifyridedata/ride.html.twig', [
            'addrideForm' => $form,
            'name' => $athleteName,
            'service' => $user->getPreferredProvider(),
        ]);
    }

    #[Route('/addride/manual', name: 'addridemanual')]
    public function addRideManual(Request $request, StravaAPI $strava_api, KomootAPI $komoot_api): Response
    {
        $athleteActivity = [];
        $user = $this->getUser();
        $ride = new Ride();
        $ride->setUser($user);
        $ride->setClubRide(false);

        //This could be a dropdown on the form
        $ride->setSource($user->getPreferredProvider());

        $form = $this->createForm(AddrideFormType::class, $ride);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //this could be improved by validation, but hey
            $firstdayofmonth = new \DateTime();
            $firstdayofmonth->modify('midnight')->modify('first day of this month');
            //Check if a ride_id has been entered and if it is valid
            $id = $form->getData()->getRideId();
            $isValidID = true;
            if ($id != null) {
                //Switch depending on provider - could be a dropdown on form!!!!
                switch ($user->getPreferredProvider()) {
                    case 'komoot':
                        //Check if the user registered with komoot
                        if ($user->getKomootID() && $user->getKomootRefreshToken()) {
                            //Get or refresh token as necessary
                            if (!$request->getSession()->get('komoot.token') || $user->getKomootTokenExpiry() - time() < 300) {
                                $accessToken = $komoot_api->getToken($user);
                                $request->getSession()->set('komoot.token', $accessToken);
                            }
                            $token = $request->getSession()->get('komoot.token');
                            $athleteActivity = $komoot_api->getAthleteActivity($token, $id);
                            $ride->setKm($athleteActivity['distance']);
                            $ride->setAverageSpeed($athleteActivity['average']);
                            $ride->setDate($athleteActivity['date']);
                            $ride->setClubRide($komoot_api->isClubRide($token, $id, $athleteActivity['date']));
                        }
                        break;
                    case 'strava':
                        //Check if the user registered with strava
                        if ($user->getStravaID() && $user->getStravaRefreshToken()) {
                            //Get or refresh token as necessary
                            if (!$request->getSession()->get('strava.token') || $user->getStravaTokenExpiry() - time() < 300) {
                                $accessToken = $strava_api->getToken($user);
                                $request->getSession()->set('strava.token', $accessToken);
                            }
                            $token = $request->getSession()->get('strava.token');
                            $athleteActivity = $strava_api->getAthleteActivity($token, $id);
                            $ride->setKm($athleteActivity['distance']);
                            $ride->setAverageSpeed($athleteActivity['average']);
                            $ride->setDate($athleteActivity['date']);
                            $ride->setClubRide($strava_api->isClubRide($token, $id, $athleteActivity['date']));
                        }
                        break;
                }
                if (!$athleteActivity) {
                    $isValidID = false;
                }
            }
            if ($form->getData()->getDate() < $firstdayofmonth) {
                $this->addFlash('danger', 'You cannot enter a ride for last month!');
            } elseif (!$isValidID) {
                $this->addFlash('danger', 'Invalid Ride ID, please check and try again.');
            } else {
                $entityManager = $this->doctrine->getManager();
                $entityManager->persist($ride);
                $entityManager->flush();

                // do anything else you need here, like send an email
                $this->addFlash('success', $this->getUser()->getName().', you have sucessfully added your ride');
                return $this->redirectToRoute('displayrides', ['username' => $this->getUser()->getUserIdentifier()]);
            }
        }
        return $this->renderForm('modifyridedata/manual.html.twig', [
            'addrideForm' => $form,
        ]);
    }
}
