<?php

namespace App\Controller;

use App\Service\RideData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CenturyController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(Request $request, RideData $rd): Response
    {
        //Read latest ride data
        $data = $rd->getRideData(year: null, username: null);

        //Read in any warning messages
        @$motd = file_get_contents('resources/motd.json');
        if (!$motd) {
            $motd = null;
        } else {
            $motd = json_decode($motd, true, 512, JSON_THROW_ON_ERROR);
            $message = '';
            foreach ($motd['message'] as $line) {
                $message .= $line;
            }
            $motd['message'] = $message;
        }

        //render the page
        return $this->render('index.html.twig', [
            'users' => $data['users'],
            'months' => $data['months'],
            'motd' => $motd,
        ]);
    }
}
