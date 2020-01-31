<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditProfileFormType;
use App\Service\KomootAPI;
use App\Service\StravaAPI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EditProfileController extends AbstractController
{
    private $komoot_api;
    private $strava_api;

    public function __construct(StravaAPI $strava_api, KomootAPI $komoot_api)
    {
        $this->komoot_api = $komoot_api;
        $this->strava_api = $strava_api;
    }

    /**
     * @Route("/profile/{username}/editprofile", name="editprofile")
     */
    public function register(Request $request)
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
                $accessToken = $this->komoot_api->getToken($user);
                $request->getSession()->set('komoot.token', $accessToken);
            }
            $komootAthlete = $this->komoot_api->getAthlete($request->getSession()->get('komoot.token'), $user->getKomootID());
        }

        $stravaAthlete = null;
        //Check if the user registered with strava
        if ($user->getStravaID() && $user->getStravaRefreshToken()) {
            //Get or refresh token as necessary
            if (!$request->getSession()->get('strava.token') || $user->getStravaTokenExpiry() - time() < 300) {
                $accessToken = $this->strava_api->getToken($user);
                $request->getSession()->set('strava.token', $accessToken);
            }
            $stravaAthlete = $this->strava_api->getAthlete($request->getSession()->get('strava.token'), $user->getStravaID());
        }

        //Create the form
        $form = $this->createForm(EditProfileFormType::class, $user, [
            'komootAthlete' => $komootAthlete,
            'stravaAthlete' => $stravaAthlete,
        ]);
        $form->handleRequest($request);
        
        //Process form data
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            // do anything else you need here, like send an email
            $this->addFlash('success', $user->getName().', you have sucessfully updated your profile');
            return $this->redirectToRoute('displayrides', ['username' => $this->getUser()->getUsername()]);
        }
        
        return $this->render('registration/edit.html.twig', [
            'registrationForm' => $form->createView(),
//            'service' => $user->getPreferredProvider(),
        ]);
    }
}
