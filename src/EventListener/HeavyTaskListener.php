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

class HeavyTaskListener
{
    public function __construct(private MailerInterface $mailer, private RouterInterface $router, private StravaAPI $strava_api, private EntityManagerInterface $em, private ManagerRegistry $doctrine)
    {
    }

    public function onKernelTerminate(TerminateEvent $event)
    {
        // What’s the current route?
        $request = $event->getRequest();
        $currentRoute = $this->router->match($request->getPathInfo());
        if ('webhook' === $currentRoute['_route']) {
            $aspect_type = $request->get('aspect_type'); // "create" | "update" | "delete"
            $object_id = $request->get('object_id'); // activity ID | athlete ID
            $object_type = $request->get('object_type'); // "activity" | "athlete"
            $owner_id = $request->get('owner_id'); // athlete ID

            if ($aspect_type == 'create' && $object_type == 'activity') {
                //Does ride exist
                $entityManager = $this->em->getRepository(Ride::class);
                if ($entityManager->findOneBy(['ride_id' => $object_id]) == null) {
                    //Get the user
                    $entityManager = $this->em->getRepository(User::class);
                    $user = $entityManager->findOneBy(['stravaID' => $owner_id]);



                    //Create a message
                    $message = (new Email())
                    ->from(new Address($_ENV['MAILER_FROM'], 'Century Challenge Contact'))
                    ->to($_ENV['MAILER_FROM'])
                    ->subject('Message from Century Challenge')
                    ->text('Message from: '.$_ENV['MAILER_FROM']."\n\r".$user['surname'])
                    ->addBcc('martyndwheeler@gmail.com');
                    /** @var Symfony\Component\Mailer\SentMessage $sentEmail */
                    $sentEmail = $this->mailer->send($message);
                
                }
            }
        }
    }
}