<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Attr;

/**
 * Explicit command handler registration.
 *
 * If applied on a class, the $target parameter will be ignored, and all the
 * target class own public methods will be registered as command handlers.
 *
 * If applied on a class method, $target parameter may be specified. It
 * registers the given method as being a single command handler which process
 * the specified target class. If target class is left unspecified, method
 * parameters will be introspected and target class deducted from it.
 *
 * In all cases, eligible methods are methods which have one and only one
 * parameter, which is typed using an existing class or interface name.
 *
 * Usage:
 *   #[CommandHandler] class Foo { }
 *   #[CommandHandler] public static function on(SomeCommand $command) {}
 *   #[CommandHandler(target: SomeCommand::class)] public static function on(SomeCommand $command) {}
 */
#[\Attribute(\Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
final class CommandHandler extends AbstractHandlerAttribute
{
}
