<?php

namespace App\EventListener;

use App\Service\StravaAPI;
use App\Entity\Ride;
use App\Entity\User;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class HeavyTaskListener
{
    public function __construct(
        private MailerInterface $mailer, 
        private RouterInterface $router,
        private StravaAPI $strava_api,
        private EntityManagerInterface $em,
        private ManagerRegistry $doctrine,
        private ValidatorInterface $validator,
        )
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

            if ($aspect_type == 'update' && $object_type == 'activity') {
                //Does ride already exist in the database
                $entityManager = $this->em->getRepository(Ride::class);
                if ($entityManager->findOneBy(['ride_id' => $object_id]) == null) {
                    //Get the user
                    $entityManager = $this->em->getRepository(User::class);
                    $user = $entityManager->findOneBy(['stravaID' => $owner_id]);
                    //is the user dqed

                    //set access token
                    $token = $this->strava_api->getToken($user);

                    //create ride object
                    $ride = new Ride();
                    $ride->setUser($user);
                    $ride->setSource($user->getPreferredProvider());

                    //get the activity from strava
                    $athleteActivity = $this->strava_api->getAthleteActivity($token, $object_id);

                    //if a valid activity is returned
                    if ($athleteActivity) {
                        $ride->setRideId($object_id);
                        $ride->setKm($athleteActivity['distance']);
                        $ride->setAverageSpeed($athleteActivity['average']);
                        $ride->setDate($athleteActivity['date']);
                        $ride->setClubRide($this->strava_api->isClubRide($token, $object_id, $athleteActivity['date']));
                        $errors = $this->validator->validate($ride);
                        if (count($errors) == 0) {
                            if ($this->strava_api->isRealRide($token, $object_id)){
                                $entityManager = $this->doctrine->getManager();
                                $entityManager->persist($ride);
                                $entityManager->flush();
                            }
                        }
                    }

                    //Create a message
                    $message = (new Email())
                    ->from(new Address($_ENV['MAILER_FROM'], 'Century Challenge Contact'))
                    ->to($_ENV['MAILER_FROM'])
                    ->subject('Message from Century Challenge')
                    ->text('Message from: '.$_ENV['MAILER_FROM']."\n\r".$user->getUsername())
                    ->addBcc('martyndwheeler@gmail.com');
                    /** @var Symfony\Component\Mailer\SentMessage $sentEmail */
                    $sentEmail = $this->mailer->send($message);
                }
            }
        }
    }
}