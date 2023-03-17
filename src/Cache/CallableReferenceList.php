<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Cache;

interface CallableReferenceList
{
    /**
     * @return null|CallableReference
     */
    public function first(string $className): ?CallableReference;

    /**
     * @return CallableReference[]
     */
    public function all(string $className): iterable;
}
