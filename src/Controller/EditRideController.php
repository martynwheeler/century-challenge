<?php

namespace App\Controller;

use App\Entity\Ride;
use App\Entity\User;
use App\Form\AddRideManFormType;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ride')]
class EditRideController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    #[Route('/{ride_id}/delete-ride', name: 'app_delete_ride')]
    public function deleteRideAction(Request $request, $ride_id): Response
    {
        //Get the ride from db
        $ride = $this->doctrine->getRepository(Ride::class)->find($ride_id);
        if (!$ride) {
            throw $this->createNotFoundException(
                "No ride found for id $ride_id"
            );
        }

        //build form
        $form = $this->createFormBuilder()->getForm();

        //process form
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($ride);
            $entityManager->flush();

            // do anything else you need here, like send an email
            /** @var User $user */
            $user = $this->getUser();
            $this->addFlash('success', "{$user->getName()}, you have successfully deleted your ride");
            return $this->redirectToRoute('app_display_rides', ['username' => $this->getUser()->getUserIdentifier()]);
        }
        return $this->renderForm('modify_ride_data/delete.html.twig', [
            'deleteRideForm' => $form,
        ]);
    }

    #[Route('/{ride_id}/edit-ride', name: 'app_edit_ride')]
    public function editRideAction(Request $request, $ride_id): Response
    {
        //Get the ride from db
        $ride = $this->doctrine->getRepository(Ride::class)->find($ride_id);
        if (!$ride) {
            throw $this->createNotFoundException(
                "No ride found for id $ride_id"
            );
        }

        //build form
        $form = $this->createForm(AddRideManFormType::class, $ride);

        //process form
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //this could be improved by validation, but hey
            $firstDayOfMonth = new DateTime();
            $firstDayOfMonth->modify('midnight')->modify('first day of this month');
            if ($form->getData()->getDate() >= $firstDayOfMonth) {
                $this->doctrine->getManager()->flush();

                // do anything else you need here, like send an email
                /** @var User $user */
                $user = $this->getUser();
                $this->addFlash('success', "{$user->getName()}, you have successfully edited your ride");
                return $this->redirectToRoute(
                    'app_display_rides',
                    ['username' => $this->getUser()->getUserIdentifier()]
                );
            } else {
                $this->addFlash('danger', 'You cannot enter a ride for last month!');
            }
        }
        return $this->renderForm('modify_ride_data/manual.html.twig', [
            'addRideForm' => $form,
        ]);
    }
}
