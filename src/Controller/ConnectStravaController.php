<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\StravaClient;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/connect')]
class ConnectStravaController extends AbstractController
{
    public function __construct(private ClientRegistry $clientRegistry, private ManagerRegistry $doctrine)
    {
    }

    #[Route('/strava', name: 'app_connect_strava')]
    public function connectAction(): RedirectResponse
    {
        // will redirect to Strava!
        return $this->clientRegistry
            ->getClient('strava_oauth') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect(['read', 'activity:read_all']) // the scopes you want to access
        ;
    }

    /**
     * After going to Strava, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     */
    #[Route('/strava/check', name: 'app_connect_strava_check')]
    public function connectCheckAction(Request $request): RedirectResponse
    {
        /** @var StravaClient $client */
        $client = $this->clientRegistry->getClient('strava_oauth');

        try {
            //Get hold of the accessToken object and get important stuff
            $accessToken = $client->getAccessToken();

            //save the short-lived token in the session
            $request->getSession()->set('strava.token', $accessToken->getToken());

            //Now store the refresh token and the expiry time of the access token in the user object
            /** @var User $user */
            $user = $this->getUser();
            $user->setStravaRefreshToken($accessToken->getRefreshToken());
            $user->setStravaTokenExpiry($accessToken->getExpires());
            $user->setStravaID($client->fetchUserFromToken($accessToken)->getId());
            $user->setPreferredProvider('strava');

            //update user object
            $this->doctrine->getManager()->flush();

            //Success - redirect accordingly
            if ($request->getSession()->remove('reconnect.strava')) {
                //you were redirected here because of an invalid token
                return $this->redirectToRoute('app_add_ride');
            }

            return $this->redirectToRoute('app_homepage');
        } catch (IdentityProviderException $e) {
            // something went wrong!
            // probably you should return the reason to the user
            dd($e->getMessage());
        }
    }
}
