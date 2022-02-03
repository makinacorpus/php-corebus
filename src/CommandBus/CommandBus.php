<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus;

interface CommandBus
{
    /**
     * Response may or may not be returned by command. Moreover, dispatching
     * can be delayed or sent asynchronously, case in which Reponse will not
     * be returned.
     */
    public function dispatchCommand(object $command): CommandResponsePromise;
}
