<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Cache;

use Symfony\Component\HttpFoundation\Request;

/**
 * Method has more than one parameter, and user specified neither a type
 * or a parameter name: we don't know which to use.
 */
class MockHandlerErrorAmbigousMultipleDeclaration
{
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler()]
    public function error(\DateTime $foo, Request $bar): void
    {
    }
}
