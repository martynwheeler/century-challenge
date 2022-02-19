<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditProfileFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EditProfileController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    #[Route('/profile/{username}/edit-profile', name: 'app_edit_profile')]
    public function editProfileAction(Request $request): Response
    {
        //Get the current user
        /** @var User $user */
        $user = $this->getUser();

        //Create the form
        $form = $this->createForm(EditProfileFormType::class, $user);

        //Process form data
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();

            //Add flash message
            $this->addFlash('success', "{$user->getName()}, you have successfully updated your profile");
            return $this->redirectToRoute('app_display_rides', ['username' => $this->getUser()->getUserIdentifier()]);
        }

        return $this->renderForm('registration/edit.html.twig', [
            'registrationForm' => $form,
            'service' => $user->getPreferredProvider(),
        ]);
    }
}
