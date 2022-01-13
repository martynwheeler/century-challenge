<?php

namespace App\Controller;

use App\Service\RideData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DisplayridesController extends AbstractController
{
    #[Route('/profile/{username}', name: 'displayrides')]
    public function index($username, RideData $rd)
    {
        $data = $rd->getRideData(year: null, username: $username);
        return $this->render('displayrides/display.html.twig', [
            'user' => $data['users'][0],
        ]);
    }
}
