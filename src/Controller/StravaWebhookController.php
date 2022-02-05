<?php

namespace App\Controller;

use App\Service\StravaWebhookService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class StravaWebhookController extends AbstractController
{
    public function __construct()
    {

    }

    #[Route('/strava/webhook', name:'webhook_create', methods: ['GET'])]
    public function create(Request $request, StravaWebhookService $stravawebhookservice): Response
    {
        $mode = $request->query->get('hub_mode'); // hub.mode
        $token = $request->query->get('hub_verify_token'); // hub.verify_token
        $challenge = $request->query->get('hub_challenge'); // hub.challenge

        $response = $stravawebhookservice->validate($mode, $token, $challenge);
        return $response;
    }

    #[Route('/strava/webhook', name:'webhook', methods: ['POST'])]
    public function data(Request $request): Response
    {
        return new Response('EVENT_RECEIVED', Response::HTTP_OK, []);
    }
}