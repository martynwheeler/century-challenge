<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConnectController extends AbstractController
{
    #[Route('/connect', name: 'connect')]
    public function connect(Request $request): Response
    {
        //Remove flag in session
        $request->getSession()->remove('reconnect.strava');
        $request->getSession()->remove('reconnect.komoot');
 
        //generate urls
        $stravaurl = $this->generateUrl('connect_strava');
        $komooturl = $this->generateUrl('connect_komoot');

        //return page as response
        return $this->render('connect/connect.html.twig', [
            'stravaurl' => $stravaurl,
            'komooturl' => $komooturl,
        ]);
    }
}
