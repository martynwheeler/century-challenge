<?php

namespace App\Command;

use App\Service\StravaWebhook;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:SubscribeToStravaWebhookCommand',
    description: 'Subscribes to a Strava webhook',
)]
class SubscribeToStravaWebhookCommand extends Command
{
    public function __construct(private StravaWebhook $stravawebhook)
    {
        parent::__construct(null);
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $id = $this->stravawebhook->subscribe();
        if ($id != null && $id > 0) {
            $io->success("A new subcription was initiated with id = $id");
        } elseif ($id != null && $id < 0) {
            $io->info("A subscription already exists");
        } else {
            $io->warning("Subcribe failed");
        }

        return Command::SUCCESS;
    }
}
