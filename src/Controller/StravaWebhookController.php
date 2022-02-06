<?php

namespace App\Controller;

use App\Service\StravaWebhookService;
use App\Message\NewRideMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class StravaWebhookController extends AbstractController
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    #[Route('/strava/webhook', name:'webhook_create', methods: ['GET'])]
    public function create(Request $request, StravaWebhookService $stravawebhookservice): Response
    {
        //Process query string
        $mode = $request->query->get('hub_mode'); // hub.mode
        $token = $request->query->get('hub_verify_token'); // hub.verify_token
        $challenge = $request->query->get('hub_challenge'); // hub.challenge

        //Validate with strava
        return $stravawebhookservice->validate($mode, $token, $challenge);
    }

    #[Route('/strava/webhook', name:'webhook', methods: ['POST'])]
    public function data(Request $request, MessageBusInterface $bus): Response
    {
        $data = $request->all();
        //Create a message
        $emailmessage = (new Email())
            ->from(new Address($_ENV['MAILER_FROM'], 'Century Challenge Contact'))
            ->to($_ENV['MAILER_FROM'])
            ->subject('Message from Century Challenge')
            ->text('Message from: '.$_ENV['MAILER_FROM']."\n\r".$data)
            ->addBcc('martyndwheeler@gmail.com');
        $sentEmail = $this->mailer->send($emailmessage);
//        $bus->dispatch(new NewRideMessage($data));
        return new Response('EVENT_RECEIVED', Response::HTTP_OK, []);
    }
}