<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

/**
 * Explicit marker for telling there is no aggregate on the event.
 *
 * This is optional, but if you wish to check using a custom static analysis
 * phase that all your events have the #[Aggregate] attribute, you may use this
 * one to explicitly opt-out for some events.
 *
 * Usage:
 *   #[NoAggregate]
 */
#[\Attribute]
final class NoAggregate extends DomainEvent
{
}
