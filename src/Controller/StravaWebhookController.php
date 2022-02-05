<?php

namespace App\Controller;

use App\Service\StravaWebhookService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class StravaWebhookController extends AbstractController
{
    public function __construct(private StravaWebhookService $stravawebhookservice)
    {
    }

    #[Route('/strava/webhook', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $mode = $request->query('hub_mode'); // hub.mode
        $token = $request->query('hub_verify_token'); // hub.verify_token
        $challenge = $request->query('hub_challenge'); // hub.challenge
        
        return $this->stravawebhookservice->validate($mode, $token, $challenge);
    }
}
