<?php

namespace App\Controller;

use App\Service\StravaWebhookService;
use App\Message\NewRideMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class StravaWebhookController extends AbstractController
{
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
        $bus->dispatch(new NewRideMessage($request->getContent()));
        return new Response('EVENT_RECEIVED', Response::HTTP_OK, []);
    }
}