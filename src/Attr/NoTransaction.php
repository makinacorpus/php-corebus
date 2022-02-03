<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

/**
 * Disable transaction for the command.
 *
 * Usage:
 *   #[NoTransaction]
 *
 * @Annotation
 */
#[\Attribute]
final class NoTransaction extends Command
{
}
