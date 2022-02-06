<?php

namespace App\MessageHandler;

use App\Service\StravaAPI;
use App\Entity\Ride;
use App\Entity\User;
use App\Message\NewRideMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class NewRideMessageHandler
{
    public function __construct(private MailerInterface $mailer, private StravaAPI $strava_api, private EntityManagerInterface $em, private ManagerRegistry $doctrine)
    {
    }

    public function __invoke(NewRideMessage $message)
    {
        //Decode json string
        $data = json_decode($message->getContent(),true);
        $aspect_type = $data['aspect_type']; // "create" | "update" | "delete"
        $object_id = $data['object_id']; // activity ID | athlete ID
        $object_type = $data['object_type']; // "activity" | "athlete"
        $owner_id = $data['owner_id']; // athlete ID

        if ($aspect_type == 'create' && $object_type == 'activity') {
            //Does ride already exist in the database
            $entityManager = $this->em->getRepository(Ride::class);
            if ($entityManager->findOneBy(['ride_id' => $object_id]) == null) {
                //Get the user
                $entityManager = $this->em->getRepository(User::class);
                $user = $entityManager->findOneBy(['stravaID' => $owner_id]);

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
                    if ($this->strava_api->isRealRide($token, $object_id)){
                        $entityManager = $this->doctrine->getManager();
//                            $entityManager->persist($ride);
//                            $entityManager->flush();
                    }
                }

                //emailmessage a message
                $emailmessage = (new Email())
                ->from(new Address($_ENV['MAILER_FROM'], 'Century Challenge Contact'))
                ->to($_ENV['MAILER_FROM'])
                ->subject('Message from Century Challenge')
                ->text('Message from: '.$_ENV['MAILER_FROM']."\n\r"."hello")
                ->addBcc('martyndwheeler@gmail.com');
                /** @var Symfony\Component\Mailer\SentMessage $sentEmail */
                $sentEmail = $this->mailer->send($emailmessage);
            }
        }
    }
}