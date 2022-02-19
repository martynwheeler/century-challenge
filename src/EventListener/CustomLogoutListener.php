<?php

namespace App\EventListener;

use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class CustomLogoutListener
{
    #[NoReturn]
    public function onLogoutEvent(LogoutEvent $logoutEvent): void
    {
        $requestUri = $logoutEvent->getRequest()->getRequestUri();
        if (strpos($requestUri, 'redirect')) {
            $httpPos = strpos($requestUri, 'http');
            $redirectUrl = substr($requestUri, $httpPos);
            $logoutEvent->setResponse(new RedirectResponse($redirectUrl, Response::HTTP_MOVED_PERMANENTLY));
        }
    }
}
