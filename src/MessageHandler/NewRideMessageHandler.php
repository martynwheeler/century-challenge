<?php

namespace App\MessageHandler;

use App\Entity\Ride;
use App\Entity\User;
use App\Message\NewRideMessage;
use App\Service\RideData;
use App\Service\StravaAPI;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class NewRideMessageHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private StravaAPI $strava_api,
        private ManagerRegistry $doctrine,
        private RideData $rd,
    ) {
    }

    public function __invoke(NewRideMessage $message)
    {
        //Decode json string
        $data = json_decode($message->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $aspect_type = $data['aspect_type']; // "create" | "update" | "delete"
        $object_id = $data['object_id']; // activity ID | athlete ID
        $object_type = $data['object_type']; // "activity" | "athlete"
        $owner_id = $data['owner_id']; // athlete ID

        if ($aspect_type == 'create' && $object_type == 'activity') {//should be create
            //Does ride already exist in the database
            if ($this->doctrine->getRepository(Ride::class)->findOneBy(['ride_id' => $object_id]) == null) {
                //Get the user
                $user = $this->doctrine->getRepository(User::class)->findOneBy(['stravaID' => $owner_id]);

                if ($user){
                    //if not dq'ed then process
                    if (!$this->rd->getRideData(year: null, username: $user->getUsername())['users'][0]['isDisqualified']) {
                        //set access token
                        $token = $this->strava_api->getToken($user);

                        //if token granted then add ride
                        if ($token) {
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
                                $ride->setClubRide($athleteActivity['isClubride']);

                                //If the ride is real then add to db
                                if ($athleteActivity['isRealride']) {
                                    $entityManager = $this->doctrine->getManager();
                                    $entityManager->persist($ride);
                                    $entityManager->flush();

                                    //emailmessage a message
                                    $emailmessage = (new Email())
                                    ->from(new Address($_ENV['MAILER_FROM'], 'Century Challenge Contact'))
                                    ->to($user->getEmail())
                                    ->subject('Message from Century Challenge')
                                    ->text('Message from: '.$_ENV['MAILER_FROM']."\n\r"."Your ride with id=$object_id has successfully been added.");
                                    $sentEmail = $this->mailer->send($emailmessage);
                                }
                            }
                        }
                    }
                }                
            }
        }
    }
}
