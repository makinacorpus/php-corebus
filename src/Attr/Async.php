<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

/**
 * Mark the command for being always run asynchronously.
 *
 * Usage:
 *   #[Async]
 */
#[\Attribute]
final class Async extends Command
{
}
