<?php

namespace App\Controller;

use App\Service\RideData;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CenturyController extends AbstractController
{
    public function __construct(private RideData $rd)
    {
    }

    /**
     * @throws JsonException
     */
    #[Route('/', name: 'app_homepage')]
    public function indexAction(): Response
    {
        //Read latest ride data
        $data = $this->rd->getRideData(year: null, username: null);

        //Read in any warning messages
        @$messageToUsers = file_get_contents('resources/message.json');
        if (!$messageToUsers) {
            $messageToUsers = null;
        } else {
            $messageToUsers = json_decode($messageToUsers, true, 512, JSON_THROW_ON_ERROR);
            $message = implode('', $messageToUsers['message']);
            $messageToUsers['message'] = $message;
        }

        //render the page
        return $this->render('index.html.twig', [
            'users' => $data['users'],
            'months' => $data['months'],
            'message' => $messageToUsers,
        ]);
    }
}
