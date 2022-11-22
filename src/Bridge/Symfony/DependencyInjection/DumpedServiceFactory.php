<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection;

/**
 * Acts as a service factory that prevents Symfony container from loading
 * twice the same file during rebuild.
 *
 * Consider you are doing a cache:clear:
 *   - symfony loads the kernel,
 *   - during that time it may initialize some services, which in the end have
 *     our dumped file as a dependency,
 *   - it moves the cache/dev/ folder somewhere else, such as cache/de_
 *     temporarily,
 *   - during rebuild, it may spawn a few more services such as route loaders
 *     for example, which in their turn will require the same file,
 *   - but the file has moved, and class name remain the same, class then
 *     become defined twice and explode.
 *
 * This factory will simply add a class_exists() wrapping call before
 * requiring the compiled/dumped class.
 *
 * Another method would have been to hash class names using some kind of
 * content hash, but with reproducible hashes, this would leave the problem
 * intact if the file content doesn't change.
 *
 * Another method would have been to use the container build identifier in
 * class name, but it would not be accessible from a compiler pass, so it
 * cannot be done.
 *
 * Another method would be to randomize the class name, but hugh, no, we
 * want builds to be reproducible.
 */
class DumpedServiceFactory
{
    public static function load(string $className, string $file): object
    {
        if (!\class_exists($className)) {
            require_once $file;
        }
        return new $className;
    }
}
