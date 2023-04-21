<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\EventStore;

/**
 * We need to be able to extract a basic set of information for the event store
 * to keep a clean history, we choose to delegate this to an extra component
 * you can implement outside of your domain, thus avoiding to pollute it with
 * external third-party dependencies.
 *
 * @deprecated
 * @codeCoverageIgnore
 */
interface EventInfoExtrator
{
    /**
     * Get meta-information about domain event.
     */
    public function extract(object $event, EventInfo $eventInfo): void;
}
