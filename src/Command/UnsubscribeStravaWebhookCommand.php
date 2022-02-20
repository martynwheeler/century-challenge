<?php

namespace App\Command;

use App\Service\StravaWebhook;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:UnsubscribeStravaWebhookCommand',
    description: 'Deletes a Strava webhook subscription',
)]
class UnsubscribeStravaWebhookCommand extends Command
{
    public function __construct(private StravaWebhook $stravaWebhook)
    {
        parent::__construct(null);
    }

    protected function configure(): void
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->stravaWebhook->unsubscribe()) {
            $io->success("Successfully unsubscribed");
        } else {
            $io->warning("Error or no subscription found");
        }

        return Command::SUCCESS;
    }
}
