<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\KomootAPI;
use App\Service\StravaAPI;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/deauthorize')]
class DeauthorizeController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private StravaAPI $strava_api,
        private KomootAPI $komoot_api,
    ) {
    }

    #[Route('/strava', name: 'app_deauthorize_strava')]
    public function deauthorizeStravaAction(): Response
    {
        //Get the current user
        /** @var User $user */
        $user = $this->getUser();

        //deauthorize from strava
        $success = $this->strava_api->deauthorize($user);
        //check for errors in response
        if (array_key_exists('errors', $success)) {
            $success = null;
            $this->addFlash('danger', "{$user->getName()}, something went wrong, please check your Strava account!");
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
            $this->addFlash('success', "{$user->getName()}, you have successfully unlinked from Strava");
        }

        return $this->redirectToRoute('app_homepage');
    }

    #[Route('/komoot', name: 'app_deauthorize_komoot')]
    public function deauthorizeKomootAction(): Response
    {
        //Get the current user
        /** @var User $user */
        $user = $this->getUser();

        //deauthorize from komoot
        $success = $this->komoot_api->deauthorize($user);
        if ($success != Response::HTTP_OK) {
            $success = null;
            $this->addFlash('danger', "{$user->getName()}, something went wrong, please check your Komoot account!");
        }

        if ($success) {
            //Now remove komoot from the user object
            $user->setKomootRefreshToken(null);
            $user->setKomootTokenExpiry(null);
            $user->setKomootID(null);
            if ($user->getStravaID()) {
                $user->setPreferredProvider('strava');
            } else {
                $user->setPreferredProvider(null);
            }

            //Persist user object
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', "{$user->getName()}, you have successfully unlinked from Komoot");
        }

        return $this->redirectToRoute('app_homepage');
    }
}
