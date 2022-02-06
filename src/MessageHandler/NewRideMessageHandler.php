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
        $data = $message->getContent();
        //Create a message
        $emailmessage = (new Email())
            ->from(new Address($_ENV['MAILER_FROM'], 'Century Challenge Contact'))
            ->to($_ENV['MAILER_FROM'])
            ->subject('Message from Century Challenge')
            ->text('Message from: '.$_ENV['MAILER_FROM']."\n\r".$data)
            ->addBcc('martyndwheeler@gmail.com');
        $sentEmail = $this->mailer->send($emailmessage);
    }
}