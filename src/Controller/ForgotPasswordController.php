<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ForgotPasswordFormType;
use App\Form\ResetPasswordFormType;
use App\Security\LoginFormAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class ForgotPasswordController extends AbstractController
{
    /**
     * @Route("/resetpassword", name="resetpassword")
     */
    public function resetPassword(Request $request, \Swift_Mailer $mailer)
    {
        $form = $this->createForm(ForgotPasswordFormType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            //Get the data from the submitted form
            $email = $form->getData()['email'];
            //The user will be sent a message regardless so begin the swiftmailer message
            $message = (new \Swift_Message())
                ->setFrom(getenv('MAILER_FROM'))
                ->setTo($email)
                ->setSubject('Message from Century Challenge')
            ;
            //Check that the user exists
            $entityManager = $this->getDoctrine()->getManager();
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user instanceof User) {
                //User not found --> send warning email
                $message->setBody($this->renderView('emails/usernotfound.html.twig', []), 'text/html');
            } else {
                //User found --> generate token and send in email
                $token = bin2hex(random_bytes(64));
                $hashedtoken = hash("md5", $token);
                $user->setPasswordRequestToken($hashedtoken);
                $date = new \DateTime();
                $date->add(new \DateInterval('PT1H'));
                $user->setRequestTokenExpiry($date);
                $entityManager->flush();
                $url = $this->generateUrl('resetpassword_confirm', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                $message->setBody(
                    $this->renderView(
                        'emails/passwordreset.html.twig',
                        [
                            'name' => $user->getName(),
                            'username' => $user->getUsername(),
                            'url' => $url
                        ]
                    ),
                    'text/html'
                )
                    // Include a plaintext version of the message
                        ->addPart(
                            $this->renderView(
                                'emails/passwordreset.txt.twig',
                                [
                                    'name' => $user->getName(),
                                    'username' => $user->getUsername(),
                                    'url' => $url
                                ]
                            ),
                            'text/plain'
                        )
                    ;
            }
            $mailer->send($message);
            $this->addFlash('success', 'You have been sent an email to '.$email.' with instructions on how to reset your password, please check your inbox and your spam folder.');
            return $this->redirectToRoute('homepage');
        }
        return $this->render('security/forgotpassword.html.twig', [
            'resetForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/resetpassword/confirm/{token}", name="resetpassword_confirm")
     */
    public function resetPasswordCheck(Request $request, $token, UserPasswordEncoderInterface $passwordEncoder, GuardAuthenticatorHandler $guardHandler, LoginFormAuthenticator $authenticator)
    {
        //test whether the link is valid
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(['passwordRequestToken' => hash("md5", $token)]);
        if (!$user instanceof User || !$token) {
            $this->addFlash('danger', "This reset link is invalid, please request a new link.");
            return $this->redirectToRoute('resetpassword');
        } else {
            $currentdate = new \DateTime();
            $expired = ($currentdate > $user->getRequestTokenExpiry());
            if ($expired) {
                $this->addFlash('danger', "The password reset link has expired, please request a new link.");
                return $this->redirectToRoute('resetpassword');
            }
        }
        
        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('newPassword')->getData();
            $password = $passwordEncoder->encodePassword($user, $plainPassword);
            $user->setPassword($password);
            $user->setPasswordRequestToken(null);
            $entityManager->flush();

            //Everyhting has gone smoothly so authenticate the user
            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main' // firewall name in security.yaml
            );
        }
        return $this->render('security/resetpassword.html.twig', [
            'changepasswordForm' => $form->createView()
        ]);
    }
}
