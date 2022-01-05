<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ConnectController extends AbstractController
{
    #[Route('/connect', name: 'connect')]
    public function index()
    {
        try {
            $stravaurl = $this->generateUrl('connect_strava');
            $komooturl = $this->generateUrl('connect_komoot');
        } catch (Exception $e) {
            print $e->getMessage();
        }
        return $this->render('connect/index.html.twig', [
            'stravaurl' => $stravaurl,
            'komooturl' => $komooturl,
        ]);
    }
}
