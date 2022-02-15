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
    name: 'app:UnsubscribeStravaWebhookCommand',
    description: 'Deletes a Strava webhook subscription',
)]
class UnsubscribeStravaWebhookCommand extends Command
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

        if ($this->stravawebhook->unsubscribe()) {
            $io->success("Successfully unsubscribed");
        } else {
            $io->warning("Error or no subscription found");
        }

        return Command::SUCCESS;
    }
}
