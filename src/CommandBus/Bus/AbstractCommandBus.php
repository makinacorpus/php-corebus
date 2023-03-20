<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Bus;

use MakinaCorpus\CoreBus\CommandBus\MessageBuilder;

abstract class AbstractCommandBus
{
    /**
     * {@inheritdoc}
     */
    public function create(object $command): MessageBuilder
    {
        return new MessageBuilder($this, $command);
    }
}
