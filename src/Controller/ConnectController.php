<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConnectController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route('/connect', name: 'app_connect')]
    public function connectAction(Request $request): Response
    {
        //Remove flag in session
        $request->getSession()->remove('reconnect.strava');
        $request->getSession()->remove('reconnect.komoot');

        //generate urls
        $stravaUrl = $this->generateUrl('app_connect_strava');
        $komootUrl = $this->generateUrl('app_connect_komoot');

        //return page as response
        return $this->render('connect/connect.html.twig', [
            'stravaUrl' => $stravaUrl,
            'komootUrl' => $komootUrl,
        ]);
    }
}
