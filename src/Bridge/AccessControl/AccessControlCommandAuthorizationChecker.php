<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\AccessControl;

use MakinaCorpus\AccessControl\Authorization;
use MakinaCorpus\CoreBus\CommandBus\CommandAuthorizationChecker;

class AccessControlCommandAuthorizationChecker implements CommandAuthorizationChecker
{
    private Authorization $authorization;

    public function __construct(Authorization $authorization)
    {
        $this->authorization = $authorization;
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted(object $command): bool
    {
        return $this->authorization->isGranted($command);
    }
}
