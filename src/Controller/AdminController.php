<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\RideData;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private RideData $rd,
        private MailerInterface $mailer,
    ) {
    }

    /**
     * Produce a list of users
     */
    #[Route('/list-users', name: 'app_list_users')]
    public function listUsersAction(): Response
    {
        $users = $this->doctrine->getRepository(User::class)->findBy([], ['surname' => 'ASC']);
        return $this->renderForm('admin/list_users.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * Send email to users
     * @throws TransportExceptionInterface
     */
    #[Route('/email-users', name: 'app_email_users')]
    public function emailUsersAction(Request $request): Response
    {
        //Create a form for text entry
        $form = $this->createFormBuilder()
        ->add('message', TextareaType::class, [
            'label' => 'Message *',
            'attr' => [
                'class' => 'input-xlarge',
                'rows' => 8,
            ],
            'required' => true,
        ])
        ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $emailFormData = $form->getData();
            //Create a message
            $message = (new Email())
            ->from(new Address($_ENV['MAILER_FROM'], 'Century Challenge Contact'))
            ->to($_ENV['MAILER_FROM'])
            ->subject('Message from Century Challenge')
            ->text(
                "Message from: {$_ENV['MAILER_FROM']}\n\r{$emailFormData['message']}"
            );
            //Add BCC to non-disqualified users
            $users = $this->rd->getRideData(year: null, username: null)['users'];
            foreach ($users as $user) {
                if (!$user['isDisqualified']) {
                    $message->addBcc($user['email']);
                }
            }
            $this->mailer->send($message);
            return $this->redirectToRoute('app_homepage');
        }
        return $this->renderForm('admin/send_email.html.twig', [
            'email_form' => $form,
        ]);
    }
}
