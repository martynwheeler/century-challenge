<?php
namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    /**
     * @Route("/contact", name="contact")
     */
    public function index(Request $request, \Swift_Mailer $mailer)
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contactFormData = $form->getData();
            $message = (new \Swift_Message())
                ->setFrom([getenv('MAILER_FROM') => 'Century Challenge Contact'])
                ->setTo([getenv('MAILER_FROM') => 'Admin', $contactFormData['fromEmail'] => $contactFormData['fullName']])
                ->setReplyTo($contactFormData['fromEmail'])
                ->setSubject('Message from Century Challenge')
                ->setBody(
                    'Message from: '.$contactFormData['fromEmail']."\n\r".$contactFormData['message']."\n\r".$contactFormData['fullName'],
                    'text/plain'
                )
            ;
            $mailer->send($message);
            $this->addFlash('success', 'Thank you for contacting the Century Challenge Admin, I will get back to you as soon as possible.');
            return $this->redirectToRoute('homepage');
        }
        return $this->render('contact/contact.html.twig', [
            'email_form' => $form->createView(),
        ]);
    }
}
