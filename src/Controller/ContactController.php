<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    #[Route('/contact', name: 'contact')]
    public function contact(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contactFormData = $form->getData();
            $message = (new Email())
                ->from(new Address($_ENV['MAILER_FROM'], 'Century Challenge Contact'))
                ->to(new Address($_ENV['MAILER_FROM'], 'Admin'), new Address($contactFormData['fromEmail'], $contactFormData['fullName']))
                ->replyTo(new Address($contactFormData['fromEmail'], $contactFormData['fullName']))
                ->subject('Message from Century Challenge')
                ->text(
                    'Message from: '.$contactFormData['fromEmail']."\n\r".$contactFormData['message']."\n\r".$contactFormData['fullName']
                )
            ;
            $this->mailer->send($message);
            $this->addFlash('success', 'Thank you for contacting the Century Challenge Admin, I will get back to you as soon as possible.');
            return $this->redirectToRoute('homepage');
        }
        return $this->renderForm('contact/contact.html.twig', [
            'email_form' => $form,
        ]);
    }
}
