<?php

namespace App\Controller;

use App\Service\RideData;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class AdminController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine) {}

    #[Route('/admin/listusers', name: 'listusers')]
    public function listUsers(Request $request, RideData $rd): Response
    {
        $users = $this->doctrine->getRepository(User::class)->findBy([], ['surname' => 'ASC']);

        return $this->renderForm('admin/listusers.html.twig', [
            'users' => $users,
        ]);
    }

    //Send an email to users
    #[Route('/admin/email', name: 'email')]
    public function sendEmail(Request $request, RideData $rd, MailerInterface $mailer): Response
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
                'Message from: '.$_ENV['MAILER_FROM']."\n\r".$emailFormData['message']
            );
            //Add BCC to non-disqualified users
            $users = $rd->getRideData(year: null, username: null)['users'];
            foreach ($users as $user) {
                if (!$user['isDisqualified']) {
                    $message->addBcc($user['email']);
                }
            }
            /** @var Symfony\Component\Mailer\SentMessage $sentEmail */
            $sentEmail = $mailer->send($message);
            return $this->redirectToRoute('homepage');
        }
        return $this->renderForm('admin/sendemail.html.twig', [
            'email_form' => $form,
        ]);
    }
/*
    #[Route('/admin/updateusers', name: 'updateusers')]
    public function updateUsers(Request $request, RideData $rd)
    {
        $entityManager = $this->doctrine->getManager();
        $users = $this->doctrine->getRepository(User::class)->findBy([], ['name' => 'ASC']);

        foreach ($users as $user) {
            $name = $user->getName();
            $names = explode(' ', $name);
            //remove null elements
            $names = array_values(array_filter($names, fn($value) => !is_null($value) && $value !== ''));
            //remove whitespace
            for ($i = 0; $i < count($names); $i++) {
                $names[$i] = preg_replace('/\s+/', '', $names[$i]);
            }
            //combine first names
            if (sizeof($names) > 2){
                $first = array_shift($names);
                $names[0] = $first . ' ' . $names[0]; 
            }
            $firstname = ucwords(array_shift($names));
            $surname = ucwords(end($names));
            $user->setForename($firstname);
            $user->setSurname($surname);
            $entityManager->flush();
        }
        return $this->renderForm('admin/listusers.html.twig', [
            'users' => $users,
        ]);
    }
*/

}
