<?php

namespace App\Command;

use App\Service\RideData;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:send-monthly-email',
    description: 'Send a monthly email to users',
)]
class SendMonthlyEmailCommand extends Command
{
    public function __construct(private RideData $rd, private MailerInterface $mailer)
    {
        parent::__construct(null);
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (date('t') == date('d')) {
            //Create a message
            $io->progressStart();
            //Create a message
            $messagetousers = "Just a gentle reminder to add your rides for ".date('F').".  ";
            $messagetousers .= "The deadline for adding any rides is midnight on last day of the month.  ";
            $messagetousers .= "If you have not submitted any rides by this time you will get the boot!\n\r";
            $messagetousers .= "Thank you, Admin.";
            $message = (new Email())
            ->from(new Address($_ENV['MAILER_FROM'], 'Century Challenge Contact'))
            ->to($_ENV['MAILER_FROM'])
            ->subject('Message from Century Challenge')
            ->text(
                "Message from: {$_ENV['MAILER_FROM']}\n\r$messagetousers"
            );
            //Add BCC to non-disqualified users
            $users = $this->rd->getRideData(year: null, username: null)['users'];
            foreach ($users as $user) {
                if (!$user['isDisqualified']) {
//                    $message->addBcc($user['email']);
                }
            }
            /** @var Symfony\Component\Mailer\SentMessage $sentEmail */
            $sentEmail = $this->mailer->send($message);
            $io->progressAdvance();
            $io->progressFinish();

            $io->success('Monthly email message sent.');
        }
        return Command::SUCCESS;
    }
}
