<?php

namespace App\EventListener;

use App\Service\StravaAPI;
use App\Entity\Ride;
use App\Entity\User;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Routing\RouterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class HeavyTaskListener
{
    public function __construct(private LoggerInterface $logger, private MailerInterface $mailer, private RouterInterface $router, private StravaAPI $strava_api, private EntityManagerInterface $em, private ManagerRegistry $doctrine)
    {
    }

    public function onKernelTerminate(TerminateEvent $event)
    {
        // What’s the current route?
        $request = $event->getRequest();
        $currentRoute = $this->router->match($request->getPathInfo());
        if ('webhook' === $currentRoute['_route']) {
            //Create a message
            $message = (new Email())
            ->from(new Address($_ENV['MAILER_FROM'], 'Century Challenge Contact'))
            ->to($_ENV['MAILER_FROM'])
            ->subject('Message from Century Challenge')
            ->text('Message from: '.$_ENV['MAILER_FROM']."\n\r"."hello")
            ->addBcc('martyndwheeler@gmail.com');
            /** @var Symfony\Component\Mailer\SentMessage $sentEmail */
            $sentEmail = $this->mailer->send($message);
        }
    }
}