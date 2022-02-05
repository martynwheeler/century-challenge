<?php

namespace App\Controller;

use App\Service\StravaWebhookService;
use App\Service\StravaAPI;
use App\Entity\Ride;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class StravaWebhookController extends AbstractController
{
    public function __construct(private StravaWebhookService $stravawebhookservice, private MailerInterface $mailer, private EntityManagerInterface $em)
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
    public function data(Request $request, StravaAPI $strava_api): Response
    {
        $aspect_type = $request->get('aspect_type'); // "create" | "update" | "delete"
        $object_id = $request->get('object_id'); // activity ID | athlete ID
        $object_type = $request->get('object_type'); // "activity" | "athlete"
        $owner_id = $request->get('owner_id'); // athlete ID

        $messagetousers = "";
        if ($aspect_type == 'create' && $object_type == 'activity') {
            $entityManager = $this->em->getRepository(User::class);
            $user = $entityManager->findBy(['stravaid' => $owner_id]);
            dd($user);
            //Get or refresh token as necessary
            if (!$request->getSession()->get('strava.token') || $user->getStravaTokenExpiry() - time() < 300) {
                $accessToken = $strava_api->getToken($user);
                $request->getSession()->set('strava.token', $accessToken);
            }
            $token = $request->getSession()->get('strava.token');
            $athleteActivity = $strava_api->getAthleteActivity($token, $object_id);
            $messagetousers = $athleteActivity['distance'];
//            $ride->setKm($athleteActivity['distance']);
//            $ride->setAverageSpeed($athleteActivity['average']);
//            $ride->setDate($athleteActivity['date']);
//            $ride->setClubRide($strava_api->isClubRide($token, $id, $athleteActivity['date']));
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
