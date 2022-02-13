<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class ConnectStravaController extends AbstractController
{
    #[Route('/connect/strava', name: 'connect_strava')]
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        // will redirect to Strava!
        return $clientRegistry
            ->getClient('strava_oauth') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect(['read', 'activity:read_all']) // the scopes you want to access
        ;
    }

    /**
     * After going to Strava, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     */
    #[Route('/connect/strava/check', name: 'connect_strava_check')]
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry, ManagerRegistry $doctrine): RedirectResponse
    {
        /** @var \MartynWheeler\OAuth2\Client\Provider\Strava $client */
        $client = $clientRegistry->getClient('strava_oauth');

        try {
            // the exact class depends on which provider you're using
            /** @var \MartynWheeler\OAuth2\Client\Provider\KomootResourceOwner $user */
            //Get hold of the accesstoken object and get importantstauff
            $accessToken = $client->getAccessToken();

            //save the short-lived token in the session 
            $request->getSession()->set('strava.token', $accessToken->getToken());

            //Now store the refresh token and the expiry time of the access token in the user object
            $user = $this->getUser();
            $user->setStravaRefreshToken($accessToken->getRefreshToken());
            $user->setStravaTokenExpiry($accessToken->getExpires());
            $user->setStravaID($client->fetchUserFromToken($accessToken)->getId());
            $user->setPreferredProvider('strava');

            //update user object
            $doctrine->getManager()->flush();

            //Success - redirect accordingly
            if ($request->getSession()->remove('reconnect.strava')) {
                //you were redirected here because of an invalid token
                return $this->redirectToRoute('addride');
            }

            return $this->redirectToRoute('homepage');
        } catch (IdentityProviderException $e) {
            // something went wrong!
            // probably you should return the reason to the user
            dd($e->getMessage());
        }
    }
}
