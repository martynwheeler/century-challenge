<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\StravaAPI;
use App\Service\KomootAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class DeauthorizeController extends AbstractController
{
    #[Route('/deauthorize/strava', name: 'deauthorize_strava')]
    public function deauthorizeStrava(Request $request, ManagerRegistry $doctrine, StravaAPI $strava_api): Response
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
            if (!$request->getSession()->get('strava.token') || $user->getStravaTokenExpiry() - time() < 30) {
                $accessToken = $strava_api->getToken($user);
                if ($accessToken) {
                    $request->getSession()->set('strava.token', $accessToken);
                }
            }
            //deauthorize from strava
            $success = $strava_api->deauthorize($request->getSession()->get('strava.token'));
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
            $doctrine->getManager()->flush();
            $this->addFlash('success', $user->getName().', you have sucessfully unlinked from Strava');
        }

        return $this->redirectToRoute('homepage');
    }

    #[Route('/deauthorize/komoot', name: 'deauthorize_komoot')]
    public function deauthorizeKomoot(Request $request, ManagerRegistry $doctrine, KomootAPI $komoot_api): Response
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
        if ($user->getKomootID() && $user->getKomootRefreshToken()) {
            //Get or refresh token as necessary
            if (!$request->getSession()->get('komoot.token') || $user->getKomootTokenExpiry() - time() < 30) {
                $accessToken = $komoot_api->getToken($user);
                if ($accessToken) {
                    $request->getSession()->set('komoot.token', $accessToken);
                }
            }
            //deauthorize from strava
            $success = $komoot_api->deauthorize($user->getKomootRefreshToken());
            if ($success != Response::HTTP_OK){
                $success = null;
                $this->addFlash('danger', $user->getName().', something went wrong, please check your Komoot account!');
            }
        }

        if ($success) {
            //Now remove strava from the user object
            $user->setKomootRefreshToken(null);
            $user->setKomootTokenExpiry(null);
            $user->setKomootID(null);
            if ($user->getStravaID()) {
                $user->setPreferredProvider('strava');
            } else {
                $user->setPreferredProvider(null);
            }

            //Persist user object
            $doctrine->getManager()->flush();
            $this->addFlash('success', $user->getName().', you have sucessfully unlinked from Komoot');
        }

        return $this->redirectToRoute('homepage');
    }
}