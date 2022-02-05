<?php

namespace App\Controller;

use App\Service\StravaWebhookService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class StravaWebhookController extends AbstractController
{
    public function __construct(private StravaWebhookService $stravawebhookservice, private MailerInterface $mailer)
    {
    }

    #[Route('/strava/webhook', name:'webhook_create', methods: ['GET'])]
    public function create(Request $request): Response
    {
        $mode = $request->query->get('hub_mode'); // hub.mode
        $token = $request->query->get('hub_verify_token'); // hub.verify_token
        $challenge = $request->query->get('hub_challenge'); // hub.challenge

        $response = $this->stravawebhookservice->validate($mode, $token, $challenge);
        return $response;
    }

    #[Route('/strava/webhook', name:'webhook', methods: ['POST'])]
    public function data(Request $request): Response
    {
        $aspect_type = $request->get['aspect_type']; // "create" | "update" | "delete"
        $object_id = $request->get('object_id'); // activity ID | athlete ID
        $object_type = $request->get('object_type'); // "activity" | "athlete"
        $owner_id = $request->get('owner_id'); // athlete ID

        $messagetousers = "";
        if ($aspect_type == 'create' && $object_type == 'activity') {
            $messagetousers = $object_id;
        }

        $message = (new Email())
        ->from(new Address($_ENV['MAILER_FROM'], 'Century Challenge Contact'))
        ->to($_ENV['MAILER_FROM'])
        ->subject('Message from Century Challenge')
        ->text(
            "Message from: {$_ENV['MAILER_FROM']}\n\r$messagetousers"
        )
        ->addBcc('martyndwheeler@gmail.com')
        ;
        $sentEmail = $this->mailer->send($message);

        return new Response('EVENT_RECEIVED', Response::HTTP_OK, []);
    }
}
