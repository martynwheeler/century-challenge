<?php

namespace App\Controller;

use App\Service\RideData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class DisplayRidesController extends AbstractController
{
    public function __construct(private RideData $rd)
    {
    }

    #[Route('/profile/{username}', name: 'app_display_rides')]
    public function displayRidesAction($username): Response
    {
        $data = $this->rd->getRideData(year: null, username: $username);
        return $this->render('display_rides/display.html.twig', [
            'user' => $data['users'][0],
        ]);
    }
}
