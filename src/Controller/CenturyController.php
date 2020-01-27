<?php

namespace App\Controller;

use App\Service\RideData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CenturyController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(Request $request, RideData $rd)
    {
        $data = $rd->getRideData(null);
        //render the page
        return $this->render('index.html.twig', [
            'users' => $data['users'],
            'months' => $data['months'],
        ]);
    }
}
