<?php

namespace App\Controller;

use App\Entity\Ride;
use App\Entity\User;
use App\Form\AddRideFormType;
use App\Form\AddRideManFormType;
use App\Service\KomootAPI;
use App\Service\StravaAPI;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/add-ride')]
class AddRideController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private StravaAPI $strava_api,
        private KomootAPI $komoot_api
    ) {
    }

    #[Route('', name: 'app_add_ride')]
    public function addRideAction(Request $request): Response
    {
        //Get the user and switch depending on provider
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getPreferredProvider()) {
            return $this->redirectToRoute('app_add_ride_manual');
        }

        $athleteName = null;
        switch ($user->getPreferredProvider()) {
            case 'komoot':
                $athlete = $this->komoot_api->getAthlete($user);
                // check for errors and redirect
                if (!$athlete) {
                    $request->getSession()->set('reconnect.komoot', true);
                    return $this->redirectToRoute('app_connect_komoot');
                }
                //token valid, get name
                $athleteName = $athlete['display_name'];
                break;

            case 'strava':
                $athlete = $this->strava_api->getAthlete($user);
                // check for errors and redirect
                if (!$athlete) {
                    $request->getSession()->set('reconnect.strava', true);
                    return $this->redirectToRoute('app_connect_strava');
                }
                //token valid, get name
                $athleteName = $athlete['firstname'] . ' ' . $athlete['lastname'];
                break;
        }

        //Build form
        $form = $this->createForm(AddRideFormType::class);

        //if no rides were found redirect to manual entry
        if (!$form->has('ride')) {
            return $this->redirectToRoute('app_add_ride_manual');
        }

        //Submitted form
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //get the selected ride
            $ride = $form->getData()['ride'];

            if ($ride) {
                $entityManager = $this->doctrine->getManager();
                $entityManager->persist($ride);
                $entityManager->flush();

                //Add flash message
                $this->addFlash('success', "{$user->getName()}, you have successfully added your ride");
            } else {
                $this->addFlash('warning', "{$user->getName()}, you cannot upload virtual rides)");
            }

            //Success
            return $this->redirectToRoute('app_display_rides', ['username' => $user->getUserIdentifier()]);
        }

        //Return the form as a response
        return $this->renderForm('modify_ride_data/ride.html.twig', [
            'addRideForm' => $form,
            'name' => $athleteName,
            'service' => $user->getPreferredProvider(),
        ]);
    }

    #[Route('/manual', name: 'app_add_ride_manual')]
    public function addRideManualAction(Request $request): Response
    {
        //Get the user and set up ride object
        /** @var User $user */
        $user = $this->getUser();
        $ride = new Ride();
        $ride->setUser($user);
        $ride->setClubRide(false);

        //Build form
        $form = $this->createForm(AddRideManFormType::class, $ride);
        $form->handleRequest($request);

        //Submitted form
        if ($form->isSubmitted() && $form->isValid()) {
            //this could be improved by validation, but hey
            $firstDayOfMonth = new DateTime();
            $firstDayOfMonth->modify('midnight')->modify('first day of this month');

            if ($form->getData()->getDate() < $firstDayOfMonth) {
                $this->addFlash('danger', 'You cannot enter a ride for last month!');
            } else {
                $entityManager = $this->doctrine->getManager();
                $entityManager->persist($ride);
                $entityManager->flush();

                // do anything else you need here, like send an email
                $this->addFlash('success', $user->getName() . ', you have successfully added your ride');
                return $this->redirectToRoute(
                    'app_display_rides',
                    ['username' => $this->getUser()->getUserIdentifier()]
                );
            }
        }
        return $this->renderForm('modify_ride_data/manual.html.twig', [
            'addRideForm' => $form,
        ]);
    }
}
