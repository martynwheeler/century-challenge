<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Doctrine\Persistence\ManagerRegistry;

class RegistrationController extends AbstractController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private UserAuthenticatorInterface $userAuthenticator,
        private FormLoginAuthenticator $formLoginAuthenticator,
        private ManagerRegistry $doctrine,
        )
    {
    }

    #[Route('/register', name: 'register')]
    public function register(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email
            // set redirect route upon authentication
            $request->getSession()->set('redirectTo', 'connect');

            return $this->userAuthenticator->authenticateUser(
                $user,
                $this->formLoginAuthenticator,
                $request
            );
        }

        return $this->renderForm('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
