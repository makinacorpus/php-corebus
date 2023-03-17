<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Authorization;

use MakinaCorpus\CoreBus\CommandBus\CommandAuthorizationChecker;

class CommandAuthorizationCheckerChain implements CommandAuthorizationChecker
{
    /** @var CommandAuthorizationChecker[] */
    private ?iterable $instances = null;

    public function __construct(?iterable $instances = null)
    {
        $this->instances = $instances;
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted(object $command): bool
    {
        if (null === $this->instances) {
            return true;
        }

        // @todo Implement different strategies?
        foreach ($this->instances as $instance) {
            if ($instance->isGranted($command)) {
                return true;
            }
        }

        return false;
    }
}
