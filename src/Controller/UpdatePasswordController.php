<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Model\UpdatePassword;
use App\Form\UpdatePasswordFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UpdatePasswordController extends AbstractController
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher, private ManagerRegistry $doctrine)
    {
    }

    #[Route('/profile/{username}/update-password', name: 'app_update_password')]
    public function updatePasswordAction(Request $request): Response
    {
        //if ($this->isCsrfTokenValid('delete-item', $submittedToken)) {
        $updatePasswordModel = new UpdatePassword();
        $form = $this->createForm(UpdatePasswordFormType::class, $updatePasswordModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $form->get('newPassword')->getData()
                )
            );
            $this->doctrine->getManager()->flush();

            // do anything else you need here, like send an email
            $this->addFlash('success', "{$user->getName()}, you have successfully updated your password.");
            return $this->redirectToRoute('app_display_rides', ['username' => $this->getUser()->getUserIdentifier()]);
        }
        return $this->renderForm('security/update_password.html.twig', [
            'updatePasswordForm' => $form,
        ]);
    }
}
