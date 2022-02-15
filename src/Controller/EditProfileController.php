<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditProfileFormType;
use App\Service\KomootAPI;
use App\Service\StravaAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class EditProfileController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine, private KomootAPI $komoot_api, private StravaAPI $strava_api)
    {
    }

    #[Route('/profile/{username}/editprofile', name: 'editprofile')]
    public function editProfile(Request $request): Response
    {
        //Get the current user
        $user = $this->getUser();

        //Create the form
        $form = $this->createForm(EditProfileFormType::class, $user, [
            'komootAthlete' => $this->komoot_api->getAthlete($user),
            'stravaAthlete' => $this->strava_api->getAthlete($user),
        ]);

        //Process form data
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();

            //Add flash message
            $this->addFlash('success', $user->getName().', you have sucessfully updated your profile');
            return $this->redirectToRoute('displayrides', ['username' => $this->getUser()->getUserIdentifier()]);
        }

        return $this->renderForm('registration/edit.html.twig', [
            'registrationForm' => $form,
            'service' => $user->getPreferredProvider(),
        ]);
    }
}