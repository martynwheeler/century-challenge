<?php

namespace App\Controller;

use App\Entity\Ride;
use App\Form\AddrideFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Routing\Annotation\Route;

class EditrideController extends AbstractController
{
    /**
     * @Route("/ride/{ride_id}/delete", name="deleteride")
     */
    public function delete(Request $request, $ride_id)
    {
        $repository = $this->getDoctrine()->getRepository(Ride::class);
        $ride = $repository->find($ride_id);
        if (!$ride) {
            throw $this->createNotFoundException(
                'No ride found for id '.$ride_id
            );
        }
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($ride);
            $entityManager->flush();
            
            // do anything else you need here, like send an email
            $this->addFlash('success', $this->getUser()->getName().', you have sucessfully deleted your ride');
            return $this->redirectToRoute('displayrides', ['username' => $this->getUser()->getUsername()]);
        }
        return $this->render('modifyridedata/delete.html.twig', [
            'deleterideForm' => $form->createView(),
        ]);
    }
    
    /**
     * @Route("/ride/{ride_id}/editride", name="editride")
     */
    public function edit(Request $request, $ride_id)
    {
        $repository = $this->getDoctrine()->getRepository(Ride::class);
        $ride = $repository->find($ride_id);
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
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->flush();

                // do anything else you need here, like send an email
                $this->addFlash('success', $this->getUser()->getName().', you have sucessfully edited your ride');
                return $this->redirectToRoute('displayrides', ['username' => $this->getUser()->getUsername()]);
            } else {
                $this->addFlash('danger', 'You cannot enter a ride for last month!');
            }
        }
        return $this->render('modifyridedata/manual.html.twig', [
            'addrideForm' => $form->createView(),
        ]);
    }
}