<?php

namespace App\Command;

use App\Service\StravaWebhook;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:SubscribeToStravaWebhookCommand',
    description: 'Subscribes to a Strava webhook',
)]
class SubscribeToStravaWebhookCommand extends Command
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
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $id = $this->stravaWebhook->subscribe();
        if ($id != null && $id > 0) {
            $io->success("A new subscription was initiated with id = $id");
        } elseif ($id != null && $id < 0) {
            $io->info("A subscription already exists");
        } else {
            $io->warning("Subscribe failed");
        }

        return Command::SUCCESS;
    }
}
