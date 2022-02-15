<?php

namespace App\Controller;

use App\Service\StravaWebhook;
use App\Message\NewRideMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class StravaWebhookController extends AbstractController
{
    public function __construct(private StravaWebhook $stravawebhook, private MessageBusInterface $bus)
    {
    }

    #[Route('/strava/webhook', name:'webhook_create', methods: ['GET'])]
    public function create(Request $request): Response
    {
        //Process query string
        $mode = $request->query->get('hub_mode'); // hub.mode
        $token = $request->query->get('hub_verify_token'); // hub.verify_token
        $challenge = $request->query->get('hub_challenge'); // hub.challenge

        //Validate with strava
        return $this->stravawebhook->validate($mode, $token, $challenge);
    }

    #[Route('/strava/webhook', name:'webhook', methods: ['POST'])]
    public function data(Request $request): Response
    {
        $this->bus->dispatch(new NewRideMessage($request->getContent()));
        return new Response('EVENT_RECEIVED', Response::HTTP_OK, []);
    }
}
