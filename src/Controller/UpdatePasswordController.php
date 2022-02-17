<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UpdatePasswordFormType;
use App\Form\Model\UpdatePassword;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Persistence\ManagerRegistry;

class UpdatePasswordController extends AbstractController
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher, private ManagerRegistry $doctrine)
    {
    }

    #[Route('/profile/{username}/updatepassword', name: 'updatepassword')]
    public function updatePassword(Request $request): Response
    {
        //if ($this->isCsrfTokenValid('delete-item', $submittedToken)) {
        $updatePasswordModel = new UpdatePassword();
        $form = $this->createForm(UpdatePasswordFormType::class, $updatePasswordModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $form->get('newPassword')->getData()
                )
            );
            $this->doctrine->getManager()->flush();

            // do anything else you need here, like send an email
            $this->addFlash('success', $user->getName().', you have sucessfully updated your password.');
            return $this->redirectToRoute('displayrides', ['username' => $this->getUser()->getUserIdentifier()]);
        }
        return $this->renderForm('security/updatepassword.html.twig', [
            'updatepasswordForm' => $form,
        ]);
    }
}
