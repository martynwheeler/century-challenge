<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;

class ConnectController extends AbstractController
{
    #[Route('/connect', name: 'connect')]
    public function index(Request $request)
    {
        try {
            $stravaurl = $this->generateUrl('connect_strava');
            $request->getSession()->remove('reconnect.strava');
            $komooturl = $this->generateUrl('connect_komoot');
        } catch (Exception $e) {
            print $e->getMessage();
        }
        return $this->render('connect/connect.html.twig', [
            'stravaurl' => $stravaurl,
            'komooturl' => $komooturl,
        ]);
    }
}
