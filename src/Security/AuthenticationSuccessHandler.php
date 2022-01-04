<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $providerKey = $token->getProviderKey();;
        $user = $token->getUser();
        //Check for missing refresh tokens and redirect is necessary
        if ($user->getKomootID() && !$user->getKomootRefreshToken()) {
            return new RedirectResponse($this->router->generate('connect_komoot'));
        } elseif ($user->getStravaID() && !$user->getStravaRefreshToken()) {
            return new RedirectResponse($this->router->generate('connect_strava'));
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }
        if ($targetPath = $request->getSession()->remove('redirectTo')) {
            return new RedirectResponse($this->router->generate($targetPath));
        }
        return new RedirectResponse($this->router->generate('homepage'));
    }
}