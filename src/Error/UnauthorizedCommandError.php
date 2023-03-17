<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Error;

class UnauthorizedCommandError extends \DomainException implements CoreBusError
{
}
