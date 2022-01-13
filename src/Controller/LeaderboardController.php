<?php

namespace App\Controller;

use App\Service\RideData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class LeaderboardController extends AbstractController
{
    #[Route('/leaderboard/{year?}', name: 'leaderboard')]
    public function leaderboard(RideData $rd, ?int $year)
    {
        //Grab an array of all rider data for the given year
        $data = $rd->getRideData(year: $year, username: null);

        return $this->render('leaderboard/full.html.twig', [
            'users' => $data['users'], 'months' => $data['months'],
        ]);
    }
}
