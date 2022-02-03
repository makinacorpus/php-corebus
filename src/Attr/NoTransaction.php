<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

/**
 * Disable transaction for the command.
 *
 * Usage:
 *   #[NoTransaction]
 */
#[\Attribute]
final class NoTransaction extends Command
{
}
