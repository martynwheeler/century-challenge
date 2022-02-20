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
    name: 'app:ViewStravaWebhookCommand',
    description: 'Views a Strava webhook subscription',
)]
class ViewStravaWebhookCommand extends Command
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

        $id = $this->stravaWebhook->view();

        if ($id != null) {
            $io->success("There is currently a subscription with id = $id");
        } else {
            $io->info("There are currently no subscriptions");
        }

        return Command::SUCCESS;
    }
}
