<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

/**
 * Tell the event bus that this event must not be stored in event store.
 *
 * Some event are purely domain events whose goal are to trigger listeners
 * but may replicate an already existing event or set of events, case in
 * which you probably don't want to store it.
 *
 * Usage:
 *   #[NoStore]
 *
 * @Annotation
 */
#[\Attribute]
final class NoStore extends DomainEvent
{
}
