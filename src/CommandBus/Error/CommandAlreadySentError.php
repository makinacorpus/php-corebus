<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\CommandBus\Error;

use MakinaCorpus\CoreBus\Error\CoreBusError;

/**
 * @codeCoverageIgnore
 */
class CommandAlreadySentError extends \LogicException implements CoreBusError
{
}
