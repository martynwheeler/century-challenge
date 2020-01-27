<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\Model\ChangePassword;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ChangePasswordController extends AbstractController
{
    /**
     * @Route("/profile/{username}/changepassword", name="changepassword")
     */
    public function changepassword(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $changePasswordModel = new ChangePassword();
        $form = $this->createForm(ChangePasswordFormType::class, $changePasswordModel);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('newPassword')->getData()
                )
            );
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            // do anything else you need here, like send an email
            $this->addFlash('success', $user->getName().', you have sucessfully changed your password.');
            return $this->redirectToRoute('displayrides', ['username' => $this->getUser()->getUsername()]);
        }
        return $this->render('security/changepassword.html.twig', [
            'changepasswordForm' => $form->createView()
        ]);
    }
}
