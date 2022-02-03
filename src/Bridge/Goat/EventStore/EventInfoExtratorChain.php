<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Goat\EventStore;

final class EventInfoExtratorChain implements EventInfoExtrator
{
    /** @var EventInfoExtrator[] */
    private iterable $extractors;

    public function __construct(iterable $extractors)
    {
        $this->extractors = $extractors;
    }

    /**
     * {@inheritdoc}
     */
    public function extract(object $event, EventInfo $eventInfo): void
    {
        foreach ($this->extractors as $extractor) {
            \assert($extractor instanceof EventInfoExtrator);
            $extractor->extract($event, $eventInfo);
        }
    }
}
