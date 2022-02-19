<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use MartynWheeler\OAuth2\Client\Provider\KomootClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/connect')]
class ConnectKomootController extends AbstractController
{
    public function __construct(private ClientRegistry $clientRegistry, private ManagerRegistry $doctrine)
    {
    }

    #[Route('/komoot', name: 'app_connect_komoot')]
    public function connectAction(): RedirectResponse
    {
        // will redirect to Komoot!
        return $this->clientRegistry
            ->getClient('komoot_oauth') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect(['profile']) // the scopes you want to access
        ;
    }

    /**
     * After going to Komoot, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     */
    #[Route('/komoot/check', name: 'app_connect_komoot_check')]
    public function connectCheckAction(Request $request): RedirectResponse
    {
        /** @var KomootClient $client */
        $client = $this->clientRegistry->getClient('komoot_oauth');

        try {
            //Get hold of the accessToken object and get important stuff
            $accessToken = $client->getAccessToken();

            //save the short-lived token in the session
            $request->getSession()->set('komoot.token', $accessToken->getToken());

            //Now store the refresh token and the expiry time of the access token in the user object
            /** @var User $user */
            $user = $this->getUser();
            $user->setKomootRefreshToken($accessToken->getRefreshToken());
            $user->setKomootTokenExpiry($accessToken->getExpires());
            $user->setKomootID($client->fetchUserFromToken($accessToken)->getId());
            $user->setPreferredProvider('komoot');

            //update user object
            $this->doctrine->getManager()->flush();

            //Success - redirect accordingly
            if ($request->getSession()->remove('reconnect.komoot')) {
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
