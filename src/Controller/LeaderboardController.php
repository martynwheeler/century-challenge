<?php

namespace App\Controller;

use App\Service\RideData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class LeaderboardController extends AbstractController
{
    /**
     * @Route("/leaderboard/{year}", name="leaderboard")
     */
    public function leaderboard(RideData $rd, $year = null)
    {
        $data = $rd->getRideData($year);

        return $this->render('leaderboard/full.html.twig', [
            'users' => $data['users'], 'months' => $data['months'],
        ]);
    }
}
