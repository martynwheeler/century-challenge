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
    public function __construct(private ManagerRegistry $doctrine, private StravaAPI $strava_api, private KomootAPI $komoot_api)
    {
    }

    #[Route('/addride', name: 'addride')]
    public function addRide(Request $request): Response
    {
        //Get the user and set up ride object
        $user = $this->getUser();
        $ride = new Ride();
        $ride->setUser($user);
        $ride->setSource($user->getPreferredProvider());

        //Switch depending on provider
        $athleteActivities = null;
        switch ($user->getPreferredProvider()) {
            case 'komoot':
                $athlete = $this->komoot_api->getAthlete($user);
                // check for errors and redirect
                if (!$athlete) {
                    $request->getSession()->set('reconnect.komoot', true);
                    return $this->redirectToRoute('connect_komoot');
                }
                //token valid, get rides
                $athleteName = $athlete['display_name'];
                $athleteActivities = $this->komoot_api->getAthleteActivitiesThisMonth($user);
                break;

            case 'strava':
                $athlete = $this->strava_api->getAthlete($user);
                // check for errors and redirect
                if (array_key_exists('errors', $athlete)) {
                    $request->getSession()->set('reconnect.strava', true);
                    return $this->redirectToRoute('connect_strava');
                }
                //token valid, grab the rides
                $athleteName = $athlete['firstname'].' '.$athlete['lastname'];
                $athleteActivities = $this->strava_api->getAthleteActivitiesThisMonth($user);
                break;

            default:
                return $this->redirectToRoute('addridemanual');
        }

        //if there are valid rides create a form with drop down list
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

        //Submitted form
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $rideID = $form->getData()->getRideId();
            $realRide = null;

            //loop over array of activities to process selected ride
            foreach ($athleteActivities as $athleteActivity) {
                if ($athleteActivity['id'] == $rideID) {
                    $ride->setKm($athleteActivity['distance']);
                    $ride->setAverageSpeed($athleteActivity['average']);
                    $ride->setDate($athleteActivity['date']);
                    switch ($user->getPreferredProvider()) {
                        case 'komoot':
                            //request and analyse ride stream
                            $checkRideStream = $this->komoot_api->processRideStream($user, $rideID, $athleteActivity['date']);
                            $ride->setClubRide($checkRideStream['isClubride']);
                            $realRide = $checkRideStream['isRealride'];
                            break;
                        case 'strava':
                            //request and analyse ride stream
                            $checkRideStream = $this->strava_api->processRideStream($user, $rideID, $athleteActivity['date']);
                            $ride->setClubRide($checkRideStream['isClubride']);
                            $realRide = $checkRideStream['isRealride'];
                            break;
                    }
                }
            }

            if ($realRide) {
                //Maybe do some error checking here?
                $entityManager = $this->doctrine->getManager();
                $entityManager->persist($ride);
                $entityManager->flush();

                //Add flash message
                $this->addFlash('success', $this->getUser()->getName().', you have sucessfully added your ride');
            } else {
                $this->addFlash('warning', $this->getUser()->getName().', you cannot upload virtual rides');
            }

            //Success
            return $this->redirectToRoute('displayrides', ['username' => $this->getUser()->getUserIdentifier()]);
        }

        //Return the form as a response
        return $this->renderForm('modifyridedata/ride.html.twig', [
            'addrideForm' => $form,
            'name' => $athleteName,
            'service' => $user->getPreferredProvider(),
        ]);
    }

    #[Route('/addride/manual', name: 'addridemanual')]
    public function addRideManual(Request $request): Response
    {
        //Get the user and set up ride object
        $user = $this->getUser();
        $ride = new Ride();
        $ride->setUser($user);
        $ride->setClubRide(false);

        //Build form
        $form = $this->createForm(AddrideFormType::class, $ride);
        $form->handleRequest($request);

        //Submitted form
        if ($form->isSubmitted() && $form->isValid()) {
            //this could be improved by validation, but hey
            $firstdayofmonth = new \DateTime();
            $firstdayofmonth->modify('midnight')->modify('first day of this month');

            if ($form->getData()->getDate() < $firstdayofmonth) {
                $this->addFlash('danger', 'You cannot enter a ride for last month!');
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
