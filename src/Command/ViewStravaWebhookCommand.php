<?php

namespace App\Command;

use App\Service\StravaWebhookService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ViewStravaWebhookCommand',
    description: 'Views a Strava webhook subscription',
)]
class ViewStravaWebhookCommand extends Command
{
    public function __construct(private StravaWebhookService $stravawebhookservice)
    {
        parent::__construct(null);
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $id = $this->stravawebhookservice->view();

        if ($id != null) {
            $io->success("There is currently a subcription with id = $id");
        } else {
            $io->info("There are currently no subscriptions");
        }

        return Command::SUCCESS;
    }
}
