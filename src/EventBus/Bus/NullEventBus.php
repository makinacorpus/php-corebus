<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\EventBus\Bus;

use MakinaCorpus\CoreBus\EventBus\EventBus;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class NullEventBus implements EventBus, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function notifyEvent(object $event): void
    {
        $this->logger->debug("NullEventBus: Received event for external dispatch: {event}, dropping", ['event' => $event]);
    }
}