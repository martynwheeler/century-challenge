<?php

namespace App\Controller;

use App\Message\NewRideMessage;
use App\Service\StravaWebhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/strava/webhook')]
class StravaWebhookController extends AbstractController
{
    public function __construct(private StravaWebhook $stravaWebhook, private MessageBusInterface $bus)
    {
    }

    #[Route('', name:'app_webhook_create', methods: ['GET'])]
    public function createAction(Request $request): Response
    {
        //Process query string
        $mode = $request->query->get('hub_mode'); // hub.mode
        $token = $request->query->get('hub_verify_token'); // hub.verify_token
        $challenge = $request->query->get('hub_challenge'); // hub.challenge

        //Validate with strava
        return $this->stravaWebhook->validate($mode, $token, $challenge);
    }

    #[Route('', name:'app_webhook', methods: ['POST'])]
    public function dataAction(Request $request): Response
    {
        $this->bus->dispatch(new NewRideMessage($request->getContent()));
        return new Response('EVENT_RECEIVED', Response::HTTP_OK, []);
    }
}
