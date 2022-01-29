<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ForgotPasswordFormType;
use App\Form\ResetPasswordFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Doctrine\Persistence\ManagerRegistry;

class ForgotPasswordController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    #[Route('/resetpassword', name: 'resetpassword')]
    public function resetPassword(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ForgotPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Get the data from the submitted form
            $email = $form->getData()['email'];
            //The user will be sent a message regardless so begin the mailer message
            $message = (new Email())
                ->from(new Address($_ENV['MAILER_FROM'], 'Century Challenge Contact'))
                ->to($email)
                ->subject('Message from Century Challenge')
            ;
            //Check that the user exists
            $entityManager = $this->doctrine->getManager();
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user instanceof User) {
                //User not found --> send warning email
                $message->html($this->renderView('emails/usernotfound.html.twig', []));
            } else {
                //User found --> generate token and send in email
                $token = bin2hex(random_bytes(64));
                $hashedtoken = hash('md5', $token);
                $user->setPasswordRequestToken($hashedtoken);
                $date = new \DateTime();
                $date->add(new \DateInterval('PT1H'));
                $user->setRequestTokenExpiry($date);
                $entityManager->flush();
                $url = $this->generateUrl('resetpassword_confirm', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                $message->html(
                    $this->renderView(
                        'emails/passwordreset.html.twig',
                        [
                            'name' => $user->getName(),
                            'username' => $user->getUserIdentifier(),
                            'url' => $url
                        ]
                    )
                )

                // Include a plaintext version of the message
                        ->text(
                            $this->renderView(
                                'emails/passwordreset.txt.twig',
                                [
                            'name' => $user->getName(),
                            'username' => $user->getUserIdentifier(),
                            'url' => $url
                        ]
                            )
                        )
                ;
            }
            $mailer->send($message);
            $this->addFlash('success', 'You have been sent an email to '.$email.' with instructions on how to reset your password, please check your inbox and your spam folder.');
            return $this->redirectToRoute('homepage');
        }
        return $this->renderForm('security/forgotpassword.html.twig', [
            'resetForm' => $form,
        ]);
    }

    #[Route('/resetpassword/confirm/{token}', name: 'resetpassword_confirm')]
    public function resetPasswordCheck(
        Request $request,
        $token,
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        FormLoginAuthenticator $formLoginAuthenticator
    ): Response {
        //test whether the link is valid
        $entityManager = $this->doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(['passwordRequestToken' => hash('md5', $token)]);
        if (!$user instanceof User || !$token) {
            $this->addFlash('danger', 'This reset link is invalid, please request a new link.');
            return $this->redirectToRoute('resetpassword');
        } else {
            $currentdate = new \DateTime();
            $expired = ($currentdate > $user->getRequestTokenExpiry());
            if ($expired) {
                $this->addFlash('danger', 'The password reset link has expired, please request a new link.');
                return $this->redirectToRoute('resetpassword');
            }
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('newPassword')->getData();
            $password = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($password);
            $user->setPasswordRequestToken(null);
            $entityManager->flush();

            //Everyhting has gone smoothly so authenticate the user
            return $userAuthenticator->authenticateUser(
                $user,
                $formLoginAuthenticator,
                $request
            );
        }
        return $this->renderForm('security/resetpassword.html.twig', [
            'changepasswordForm' => $form,
        ]);
    }
}
