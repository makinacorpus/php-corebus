# CoreBus - Command and Event buses interfaces

Discrete command bus and domain event dispatcher interfaces for message based
architectured projects.

Discrete means that your domain code will not be tainted by this component
code dependency, aside attributes used for targeting command handler methods
and event listener methods. Your domain business code remains dependency-free.

What does this package provide:

 - Internal synchronous event dispatcher.
 - Event listener locator.
 - Internal synchronous command bus dispatcher implementation.
 - Command process transaction handling.
 - Command handler locator.
 - Attributes for aspect-driven domain code configuration.
 - Event listener and command handler efficient caching mechanism.
 - Simple command bus interface.

What it does WILL provide (but is not there yet):

 - Message broker (asynchronous command bus) implementation.

# Design

## Basic design

Expected runtime flow is the following:

 - Commands may be dispatched to trigger writes in the system.
 - Commands are always asynchronously handled, they may return a response.
 - One command implies one transaction on your database backend.
 - During a single command processing, the domain code may raise one or many
   domain events.
 - Domain events are always dispatched synchronously within your domain
   code, within the triggering command transaction.

During the whole command processing, the database transaction will be
isolated if the backend permits it. Commit is all or nothing, including
events being emitted during the process.

## Transaction and event buffer

Transaction handling will be completely hidden in the implementations,
your business code will never see it, here is how it works:

 - Domain events while emitted and dispatched internally are stored along
   the way into a volatile in-memory temporary buffer.
 - Once command is comsumed and task has ended, transaction will commit.
 - In case of success, buffer is flushed and events may be sent to a bus
   for external application to listen to.
 - In case of failure, transaction rollbacks, event buffer is emptied,
   events are discarded without further action.

Transactions can be disabled on a per-command basis, using PHP attributes
on the command class.

## Optional event store

If required for your project, you may plug an event store on the event
dispatcher. Two options are possible:

 - Plug in into the internal event dispatcher, events will be stored along
   the way, this requires that the event store works on the same database
   transaction, hence connection, than your domain repositories.
 - Plug in into the event buffer output, which means events will be stored
   after commit, there is no consistency issues anymore, but if event storage
   procedure fails, you will loose history.

## Implementations

Two implementations are provided:

 - In-memory bus, along with null transaction handling (no transaction at all)
   ideal for prototyping and unit-testing.
 - PostgreSQL bus implementation using `makinacorpus/goat-query`, transaction
   handling using the same database connection, reliable and guaranteing data
   consistency.

Everything is hidden behind interfaces and different implementations are easy
to implement. Your projects are not required to choose either one of those
implementations, in the opposite, is encouraged implementing its own.

# Setup

## Standalone

There is no standalone setup guide for now. Refer to provided Symfony
configuration for a concrete example.

## Using Symfony

As of today, this package does not provide Symfony extension, bundle or
auto-configuration, but rather simple example configuration files for your
services and a couple of compiler pass to apply for automatic command handler
and event listener registration.

Please read carefuly one of the files in `samples/symfony/services-*.yaml`,
copy paste its contents into your own `config/services.yaml` and adapt to your
needs.

Then add into your `Kernel.php` file:

```php

use MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\Compiler\RegisterCommandHandlerPass;
use MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\Compiler\RegisterEventInfoExtractorPass;
use MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\Compiler\RegisterEventListenerPass;
use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandBusAware;
use MakinaCorpus\CoreBus\EventBus\EventBus;
use MakinaCorpus\CoreBus\EventBus\EventBusAware;
// ... Your other use statements.

class Kernel extends BaseKernel
{
    // ... Your kernel code.

    /**
     * {@inheritdoc}
     */
    protected function build(ContainerBuilder $container)
    {
        $container
            ->registerForAutoconfiguration(CommandHandler::class)
            ->addTag('app.handler')
        ;

        $container
            ->registerForAutoconfiguration(EventListener::class)
            ->addTag('app.handler')
        ;

        $container
            ->registerForAutoconfiguration(EventBusAware::class)
            ->addMethodCall('setEventBus', [new Reference(EventBus::class)])
        ;

        $container
            ->registerForAutoconfiguration(CommandBusAware::class)
            ->addMethodCall('setCommandBus', [new Reference(CommandBus::class)])
        ;

        $container->addCompilerPass(new RegisterCommandHandlerPass());
        $container->addCompilerPass(new RegisterEventListenerPass());
        $container->addCompilerPass(new RegisterEventInfoExtractorPass());
    }
```

This way, this bundle remains very flexible regarding Symfony version, and
leave all complex configuration to you, allowing you much more flexibility in
your application design.

# Usage

## Commands and events

Write a simple command:

```php

declare(strict_types=1);

final class SayHelloCommand
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
```

Write some events:

```php

declare(strict_types=1);

final class HelloWasSaidEvent
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
```

## Register handlers using base class


Tie a single command handler:

```php

declare(strict_types=1);

use MakinaCorpus\CoreBus\CommandBus\AbstractCommandHandler;

final class SayHello extends AbstractCommandHandler
{
    /*
     * Method name is yours, you may have more than one handler in the
     * same class, do you as wish. Only important thing is to implement
     * the Handler interface (here via the AbstractHandler class).
     */
    public function do(SayHelloCommand $command)
    {
        echo "Hello, ", $command->name, "\n";

        $this->notifyEvent(new HelloWasSaidEvent($command->name));
    }
}
```

You may also write as many event listeners as you wish, then even
may emit events themselves:

```php

declare(strict_types=1);

use MakinaCorpus\CoreBus\EventBus\EventListener;

final class SayHello implements EventListener
{
    /*
     * Method name is yours, you may have more than one handler in the
     * same class, do you as wish. Only important thing is to implement
     * the EventListener interface.
     */
    public function on(HelloWasSaidEvent $event)
    {
        $this->logger->debug("Hello was said to {name}.", ['name' => $event->name]);
    }
}
```

If you correctly plug the Symfony container machinery, glue will be
completely transparent as long as you implement the correct interfaces.

## Register handlers using attributes


Tie a single command handler:

```php

declare(strict_types=1);

use MakinaCorpus\CoreBus\EventBus\EventBusAware;
use MakinaCorpus\CoreBus\EventBus\EventBusAwareTrait;

final class SayHello EventBusAware
{
    use EventBusAwareTrait;

    /*
     * Method name is yours, you may have more than one handler in the
     * same class, do you as wish. Only important thing is to implement
     * the Handler interface (here via the AbstractHandler class).
     */
    #[MakinaCorpus\CoreBus\Attr\CommandHandler]
    public function do(SayHelloCommand $command)
    {
        echo "Hello, ", $command->name, "\n";

        $this->notifyEvent(new HelloWasSaidEvent($command->name));
    }
}
```

You may also write as many event listeners as you wish, then even
may emit events themselves:

```php

declare(strict_types=1);

final class SayHello
{
    /*
     * Method name is yours, you may have more than one handler in the
     * same class, do you as wish. Only important thing is to implement
     * the EventListener interface.
     */
    #[MakinaCorpus\CoreBus\Attr\EventListener]
    public function on(HelloWasSaidEvent $event)
    {
        $this->logger->debug("Hello was said to {name}.", ['name' => $event->name]);
    }
}
```

If you correctly plug the Symfony container machinery, glue will be
completely transparent as long as you implement the correct interfaces.

# Using attributes

This package comes with an attribute support for annotating commands and events
in order to infer behaviors to the bus. This allows to declare commands or
event behaviour without tainting the domain code.

## Command attributes

 - `#[MakinaCorpus\CoreBus\Attr\Async]` forces the command to
   always be dispatched asynchronously.

 - `#[MakinaCorpus\CoreBus\Attr\NoTransaction]` disables transaction
   handling for the command. Use it wisely.

 - `#[MakinaCorpus\CoreBus\Attr\Retry(?int)]` allows the command to
   be retried in case an error happen. First parameter is the number of retries
   allowed, default is `3`.

## Domain event attributes.

 - `#[MakinaCorpus\CoreBus\Attr\Aggregate(string, ?string)]`
   allows the developer to explicitely tell which aggregate (entity or model)
   this event targets. First argument must be a property name of the event that
   is the aggregate identifier, second argument is optional, and is the target
   aggregate class or logicial name. If you are using an event store, aggregate
   type is only mandatory for aggregate stream creation events, identifier will
   be enough for appending event in an existing stream.

## Configuration attributes

 - `#[MakinaCorpus\CoreBus\Attr\CommandHandler]` if set on a class, will force
   the bus to introspect all methods and register all its methods as command
   handlers, if on a single method, will register this explicit method as being
   a command handler.

 - `#[MakinaCorpus\CoreBus\Attr\EventListener]` if set on a class, will force
   the bus to introspect all methods and register all its methods as event
   listeners, if on a single method, will register this explicit method as being
   an event listener.

For all those attributes, parameters are optional, but you might set the
`target` parameter to disambiguate which class the handler or listener catches.
Using this, you can use interfaces for matching instead of concrete classes.

# Overriding implementations

Any interface in this package is a service in the dependency injection container
you will use. You may replace or decorate any of them.

# Future work

## Add a Handler attribute

`MakinaCorpus\CoreBus\Attr\Handler` attribute will allow to explicitely
declare a handler method, arguments will be the command object class or command
logical name (eg. `my_app.do_this` for example).

Using a logical name will allow handlers to function on non-existing classes
with a generic command class, or any type of data.

## Add a GenericCommand class

### Goal

This class will be a simple but dynamic DTO whoses values are dynamically
hydrated as an array from the decoded command input.

This class will yield its logical name and raw values.

This class in conjunction with the `Handler` attribute will allow developers
to define and consume commands without writing their equivalent PHP classes.

Downside of using such generic command class is that the user will not be able
to use access or any other attributes on their commands.

### Generic command definition

A new command registry will carry all user-defined commands, using their logical
names as keys, and their mapped PHP class. For generic commands, it may also
carry values list and values types, for hydration. This will pave the way for an
automatic input data validation based upon the definition.

## Handler argument resolver

This pluggable component will allow users to rely upon automatic service
injection as handler method typed arguments. Of course implementation and
details will depend upon the framework implementation.
