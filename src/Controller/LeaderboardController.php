<?php

namespace App\Controller;

use App\Service\RideData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LeaderboardController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route('/leaderboard/{year?}', name: 'app_leaderboard')]
    public function leaderboardAction(RideData $rd, ?int $year): Response
    {
        //Grab an array of all rider data for the given year
        $data = $rd->getRideData(year: $year, username: null);

        return $this->render('leaderboard/full.html.twig', [
            'users' => $data['users'], 'months' => $data['months'],
        ]);
    }
}
