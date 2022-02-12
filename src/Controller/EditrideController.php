<?php

namespace App\Controller;

use App\Entity\Ride;
use App\Form\AddrideFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class EditrideController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    #[Route('/ride/{ride_id}/delete', name: 'deleteride')]
    public function delete(Request $request, $ride_id): Response
    {
        $ride = $this->doctrine->getRepository(Ride::class)->find($ride_id);
        if (!$ride) {
            throw $this->createNotFoundException(
                'No ride found for id '.$ride_id
            );
        }
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($ride);
            $entityManager->flush();

            // do anything else you need here, like send an email
            $this->addFlash('success', $this->getUser()->getName().', you have sucessfully deleted your ride');
            return $this->redirectToRoute('displayrides', ['username' => $this->getUser()->getUserIdentifier()]);
        }
        return $this->renderForm('modifyridedata/delete.html.twig', [
            'deleterideForm' => $form,
        ]);
    }

    #[Route('/ride/{ride_id}/editride', name: 'editride')]
    public function edit(Request $request, $ride_id): Response
    {
        $ride = $this->doctrine->getRepository(Ride::class)->find($ride_id);
        if (!$ride) {
            throw $this->createNotFoundException(
                'No ride found for id '.$ride_id
            );
        }
        $form = $this->createForm(AddrideFormType::class, $ride);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //this could be improved by validation, but hey
            $firstdayofmonth = new \DateTime();
            $firstdayofmonth->modify('midnight')->modify('first day of this month');
            if ($form->getData()->getDate() >= $firstdayofmonth) {
                $this->doctrine->getManager()->flush();

                // do anything else you need here, like send an email
                $this->addFlash('success', $this->getUser()->getName().', you have sucessfully edited your ride');
                return $this->redirectToRoute('displayrides', ['username' => $this->getUser()->getUserIdentifier()]);
            } else {
                $this->addFlash('danger', 'You cannot enter a ride for last month!');
            }
        }
        return $this->renderForm('modifyridedata/manual.html.twig', [
            'addrideForm' => $form,
        ]);
    }
}
