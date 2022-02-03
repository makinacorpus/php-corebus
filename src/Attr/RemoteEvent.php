<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

/**
 * Remote event are allowed to be sent in the internal bus outside of a running
 * transaction. If it happens, we consider they come from a third-party service
 * hitting the bus, and a transaction around this even will be created.
 *
 * Usage:
 *   #[RemoteEvent]
 */
#[\Attribute]
final class RemoteEvent extends Command
{
}
