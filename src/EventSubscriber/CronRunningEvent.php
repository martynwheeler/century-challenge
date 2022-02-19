<?php

//see https://dev.to/fadymr/use-symfony-messenger-without-supervisor-3cl6

namespace App\EventSubscriber;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;

class CronRunningEvent implements EventSubscriberInterface
{
    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        if ($event->isWorkerIdle()) {
            $event->getWorker()->stop();
        }
    }

    #[ArrayShape([WorkerRunningEvent::class => "string"])]
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerRunningEvent::class => 'onWorkerRunning',
        ];
    }
}
