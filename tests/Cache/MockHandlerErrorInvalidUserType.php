<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Cache;

class MockHandlerErrorInvalidUserType
{
    #[\MakinaCorpus\CoreBus\Attr\CommandHandler(target: "this_is_not_a_class_or_interface")]
    public function error($object): void
    {
    }
}
