<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class ConnectKomootController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine) {}

    #[Route('/connect/komoot', name: 'connect_komoot')]
    public function connectAction(ClientRegistry $clientRegistry)
    {
        // on Symfony 3.3 or lower, $clientRegistry = $this->get('knpu.oauth2.registry');

        // will redirect to Komoot!
        return $clientRegistry
            ->getClient('komoot_oauth') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect(['profile']) // the scopes you want to access
        ;
    }

    /**
     * After going to Komoot, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     */
    #[Route('/connect/komoot/check', name: 'connect_komoot_check')]
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
        /** @var \MartynWheeler\OAuth2\Client\Provider\Komoot $client */
        $client = $clientRegistry->getClient('komoot_oauth');

        try {
            // the exact class depends on which provider you're using
            /** @var \MartynWheeler\OAuth2\Client\Provider\KomootResourceOwner $user */
            //Get hold of the accesstoken object and get importantstauff
            $accessToken = $client->getAccessToken();
            $request->getSession()->set('komoot.token', $accessToken);
            $username = $client->fetchUserFromToken($accessToken)->getId();
            $refresh = $accessToken->getRefreshToken();
            $expires = $accessToken->getExpires();

            //Now store the refresh token and the expiry time of the access token in the user object
            $user = $this->getUser();
            $user->setKomootRefreshToken($refresh);
            $user->setKomootTokenExpiry($expires);
            $user->setKomootID($username);
            $user->setPreferredProvider('komoot');

            //Persist user object
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            //Success - return to the home page
            return $this->redirectToRoute('homepage');
        } catch (IdentityProviderException $e) {
            // something went wrong!
            // probably you should return the reason to the user
            var_dump($e->getMessage());
            die;
        }
    }
}
