<?php

namespace App\EventListener;

use App\Service\StravaAPI;
use App\Entity\Ride;
use App\Entity\User;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Routing\RouterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class HeavyTaskListener
{
      public function __construct(private RouterInterface $router, private StravaAPI $strava_api, private EntityManagerInterface $em, private ManagerRegistry $doctrine)
     {
     }

     public function onKernelTerminate(TerminateEvent $event)
     {
            // What’s the current route?
            $request = $event->getRequest();
            $currentRoute = $this->router->match($request->getPathInfo());
            if ('webhook' === $currentRoute['_route']) {
                
            }
      }
}