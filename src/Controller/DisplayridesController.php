<?php

namespace App\Controller;

use App\Service\RideData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class DisplayridesController extends AbstractController
{
    public function __construct(private RideData $rd)
    {
    }

    #[Route('/profile/{username}', name: 'displayrides')]
    public function display($username): Response
    {
        $data = $this->rd->getRideData(year: null, username: $username);
        return $this->render('displayrides/display.html.twig', [
            'user' => $data['users'][0],
        ]);
    }
}
