<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\StravaAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class DeauthorizeController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine, private StravaAPI $strava_api)
    {
    }

    #[Route('/deauthorize/strava', name: 'deauthorize_strava')]
    public function deauthorize(Request $request): Response
    {
        //Get the current user
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException(
                'User not found'
            );
        }

        $success = null;
        //Check if the user registered with strava
        if ($user->getStravaID() && $user->getStravaRefreshToken()) {
            //Get or refresh token as necessary
            if (!$request->getSession()->get('strava.token') || $user->getStravaTokenExpiry() - time() < 300) {
                $accessToken = $this->strava_api->getToken($user);
                if ($accessToken) {
                    $request->getSession()->set('strava.token', $accessToken);
                }
            }
            //deauthorize from strava
            $success = $this->strava_api->deauthorize($request->getSession()->get('strava.token'));
            //check for errors in response
            if (array_key_exists('errors', $success)) {
                $success = null;
                $this->addFlash('danger', $user->getName().', something went wrong, please check your Strava account!');
            }
        }

        if ($success) {
            //Now remove strava from the user object
            $user->setStravaRefreshToken(null);
            $user->setStravaTokenExpiry(null);
            $user->setStravaID(null);
            if ($user->getKomootID()) {
                $user->setPreferredProvider('komoot');
            } else {
                $user->setPreferredProvider(null);
            }

            //Persist user object
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', $user->getName().', you have sucessfully unlinked from Strava');
        }

        return $this->redirectToRoute('homepage');
    }
}
