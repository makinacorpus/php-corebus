<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

/**
 * Explicit event listener registration.
 *
 * If applied on a class, the $target parameter will be ignored, and all the
 * target class own public methods will be registered as event listeners.
 *
 * If applied on a class method, $target parameter may be specified. It
 * registers the given method as being a single event listener which process
 * the specified target class. If target class is left unspecified, method
 * parameters will be introspected and target class deducted from it.
 *
 * In all cases, eligible methods are methods which have one and only one
 * parameter, which is typed using an existing class or interface name.
 *
 * Usage:
 *   #[EventListener] class Foo { }
 *   #[EventListener] public static function on(SomeEvent $event) {}
 *   #[EventListener(target: SomeEvent::class)] public static function on(SomeEvent $event) {}
 */
#[\Attribute(\Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
final class EventListener extends AbstractHandlerAttribute
{
}
