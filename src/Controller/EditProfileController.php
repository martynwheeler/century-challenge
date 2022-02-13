<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditProfileFormType;
use App\Service\KomootAPI;
use App\Service\StravaAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class EditProfileController extends AbstractController
{
    #[Route('/profile/{username}/editprofile', name: 'editprofile')]
    public function editProfile(Request $request, ManagerRegistry $doctrine, KomootAPI $komoot_api, StravaAPI $strava_api): Response
    {
        //Get the current user
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException(
                'User not found'
            );
        }

        $komootAthlete = null;
        //Check if the user registered with komoot
        if ($user->getKomootID() && $user->getKomootRefreshToken()) {
            //Get or refresh token as necessary
            if (!$request->getSession()->get('komoot.token') || $user->getKomootTokenExpiry() - time() < 300) {
                $accessToken = $komoot_api->getToken($user);
                $request->getSession()->set('komoot.token', $accessToken);
            }
            $komootAthlete = $komoot_api->getAthlete($request->getSession()->get('komoot.token'), $user->getKomootID());
        }

        $stravaAthlete = null;
        //Check if the user registered with strava
        if ($user->getStravaID() && $user->getStravaRefreshToken()) {
            //Get or refresh token as necessary
            if (!$request->getSession()->get('strava.token') || $user->getStravaTokenExpiry() - time() < 300) {
                $accessToken = $strava_api->getToken($user);
                if ($accessToken) {
                    $request->getSession()->set('strava.token', $accessToken);
                }
            }
            //grab the athlete details from strava
            $stravaAthlete = $strava_api->getAthlete($request->getSession()->get('strava.token'));
            //check for errors in response
            if (array_key_exists('errors', $stravaAthlete)) {
                $stravaAthlete = null;
            }
        }

        //Create the form
        $form = $this->createForm(EditProfileFormType::class, $user, [
            'komootAthlete' => $komootAthlete,
            'stravaAthlete' => $stravaAthlete,
        ]);
        $form->handleRequest($request);

        //Process form data
        if ($form->isSubmitted() && $form->isValid()) {
            $doctrine->getManager()->flush();

            // do anything else you need here, like send an email
            $this->addFlash('success', $user->getName().', you have sucessfully updated your profile');
            return $this->redirectToRoute('displayrides', ['username' => $this->getUser()->getUserIdentifier()]);
        }

        return $this->renderForm('registration/edit.html.twig', [
            'registrationForm' => $form,
            'service' => $user->getPreferredProvider(),
        ]);
    }
}
