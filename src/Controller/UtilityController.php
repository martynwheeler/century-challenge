<?php

namespace App\Controller;
use App\Entity\User;
use App\Entity\Ride;
use App\Service\RideData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class UtilityController extends AbstractController
{
    /**
     * @Route("/admin/fixuser", name="fixuser")
     */
    public function fixuser()
    {
        $users = $this->getDoctrine()
            ->getRepository(User::class)
            ->findAll();
        $entityManager = $this->getDoctrine()->getManager();
        foreach($users as $user) {
            if ($user->getStravaID()) {
                $user->setPreferredProvider('strava');
                $entityManager->persist($user);
            }
        }
        $entityManager->flush();
        echo "success"; die;
    }

    /**
     * @Route("/admin/fixride", name="fixride")
     */
    public function fixride()
    {
        $rides = $this->getDoctrine()
            ->getRepository(Ride::class)
            ->findAll();
        $entityManager = $this->getDoctrine()->getManager();
        foreach($rides as $ride) {
            if ($ride->getRideId()) {
                $ride->setSource('strava');
                $entityManager->persist($ride);
            }
        }
        $entityManager->flush();
        echo "success"; die;
    }


    /**
     * @Route("/admin/email", name="email")
     */
    public function index($year = null, RideData $rd, \Swift_Mailer $mailer)
    {
        $users = $rd->getRideData($year)['users'];
        $emails = [];
        foreach($users as $user){
            if (!$user['isDisqualified']) {
                $emails[] = $user['email'];
            }
        }

        die;

        $message = (new \Swift_Message())
            ->setFrom([getenv('MAILER_FROM') => 'Century Challenge Contact'])
            ->setTo([getenv('MAILER_FROM') => 'Admin'])
            ->setSubject('Message from Century Challenge')
            /**
            ->setBody(
                'You are signed up for the century challenge and have not yet added your rides for the last month.  '.
                'The deadline for adding rides is the last day of the month otherwise you will get the boot!'.PHP_EOL.
                'Thank you, Admin.',
                'text/plain'
            )
            */
            ->setBody(
                'Dear LFCC Century Challenge User,'.PHP_EOL.
                'You are currently signed up for the LFCC century challenge.  '.
                'If you no longer wish to be signed up please reply to this message and I will remove you from the database.  '.
                'Do not forget to add your 100km rides before the end of January to avoid being disqualified!'.PHP_EOL.
                'Thank you, Admin',
                'text/plain'
            )
        ;
        foreach($emails as $email){
            $message->addBcc($email);
        }
        $mailer->send($message);

        return $this->render('utilities/email.html.twig', [
            'users' => $users
        ]);
    }
}
