<?php

namespace App\Controller;

use App\Service\RideData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class DisplayridesController extends AbstractController
{
    #[Route('/profile/{username}', name: 'displayrides')]
    public function display($username, RideData $rd): Response
    {
        $data = $rd->getRideData(year: null, username: $username);
        return $this->render('displayrides/display.html.twig', [
            'user' => $data['users'][0],
        ]);
    }
}
