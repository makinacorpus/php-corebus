<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

/**
 * Force command to be rethrown as event in the bus.
 *
 * Usage:
 *   #[CommandAsEvent]
 */
#[\Attribute]
final class CommandAsEvent extends Command
{
}
