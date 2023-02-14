# CoreBus - Command and Event buses interfaces

Discrete command bus and domain event dispatcher interfaces for message based
architectured projects.

Discrete means that your domain code will not be tainted by this component
hard-dependency, aside attributes used for targeting command handler methods
and event listener methods. Your domain business code remains dependency-free.

Event bus features:

 - Internal synchronous event dispatcher.
 - Event listener locator based upon PHP attributes.
 - Event listener locator fast dumped PHP cache.
 - External event dispatcher (unplugged yet).

Command bus features:

 - Transactional synchronous command bus that handles your transactions.
 - Event buffering during transactions, which flushes events to the external
   event dispatcher only in case of transaction success.
 - Command handler locator based upon PHP attributes.
 - Command handler locator fast dumped PHP cache.
 - Default transaction implementation using `makinacorpus/goat-query`.
 - Command asynchronous dispatcher with implementation that plugs to
   `makinacorpus/message-broker` message broker interface.

Other various features:

 - Worker object for consuming asynchronous events in CLI.
 - Symfony integration for everything, including console commands for the
   command bus worker.
 - Global attributes for aspect-driven domain code configuration.
 - Simple command bus interface.

# Design

## Basic design

Expected runtime flow of your application is the following:

 - Commands may be dispatched to trigger writes in the system.
 - Commands are always asynchronously handled, they may return a response.
 - One command implies one transaction on your database backend.
 - During a single command processing, the domain code may raise one or many
   domain events.
 - Domain events are always dispatched synchronously within your domain
   code, within the triggering command transaction.

During the whole command processing, the database transaction will be
isolated if the backend permits it. Commit is all or nothing, including
events being emitted and listener execution during the process.

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
 - PostgreSQL bus implementation using `makinacorpus/goat` for message broker,
   and `makinacorpus/goat-query` for transaction handling using the same
   database connection, reliable and guaranteing data consistency.

Everything is hidden behind interfaces and different implementations are easy
to implement. Your projects are not required to choose either one of those
implementations, in the opposite, is encouraged implementing its own.

# Setup

## Standalone

There is no standalone setup guide for now. Refer to provided Symfony
configuration for a concrete example.

## Symfony

Simply enable the bundle in your `config/bundles.php` file:

```php
return [
    // ... your other bunbles.
    MakinaCorpus\CoreBus\Bridge\Symfony\CoreBusBundle::class => ['all' => true],
];

```

Then cut and paste `src/Bridge/Symfony/Resources/example/corebus.sample.yaml`
file into your `config/packages/` folder, and edit it.

# Usage

## Commands and events

Commands are plain PHP object and don't require any dependency.

Just write a Data Transport Object:

```php
namespace App\Domain\SomeBusiness\Command;

final class SayHelloCommand
{
    public readonly string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
```

Same goes with events, so just write:

```php
namespace App\Domain\SomeBusiness\Event;

final class HelloWasSaidEvent
{
    public readonly string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
```

## Register handlers using base class

Tie a single command handler:

```php
namespace App\Domain\SomeBusiness\Handler;

use MakinaCorpus\CoreBus\CommandBus\AbstractCommandHandler;

final class SayHelloHandler extends AbstractCommandHandler
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

Please note that using the `AbstractCommandHandler` base class is purely
optional, it's simply an helper for being able to use the event dispatcher
and command bus from within your handlers.

Alternatively, if you don't require any of those, you may just:

 - Either set the `#[MakinaCorpus\CoreBus\Attr\CommandHandler]` attribute on
   the class, case in which all of its methods will be considered as handlers.
 - Either set the `#[MakinaCorpus\CoreBus\Attr\CommandHandler]` attribute on
   each method that is an handler.

You may also write as many event listeners as you wish, then even
may emit events themselves:

```php
namespace App\Domain\SomeBusiness\Listener;

use MakinaCorpus\CoreBus\EventBus\EventListener;

final class SayHelloListener implements EventListener
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

Same goes for event listeners, the base class is just here to help
but is not required, you may just:

 - Either set the `#[MakinaCorpus\CoreBus\Attr\EventListener]` attribute on
   the class, case in which all of its methods will be considered as listeners.
 - Either set the `#[MakinaCorpus\CoreBus\Attr\EventListener]` attribute on
   each method that is an listener.

This requires that your services are known by the container. You have three
different options for this.

First one, which is Symfony's default, autoconfigure all your services:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    everything:
        namespace: App\Domain\
        resource: '../src/Domain/*'
```

Or if you wish to play it subtle:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    handler_listener:
        namespace: App\Domain\
        resource: '../src/Domain/*/{Handler,Listener}'
```

Or if you want to do use the old ways:

```yaml
services:
    App\Domain\SomeBusiness\Handler\SayHelloHandler: ~
    App\Domain\SomeBusiness\Listener\SayHelloListener: ~
```

In all cases, you don't require any tags or any other metadata as long as you
either extend the base class, or use the attributes.

## Register handlers using attributes


Tie a single command handler:

```php
namespace App\Domain\SomeBusiness\Handler;

use MakinaCorpus\CoreBus\EventBus\EventBusAware;
use MakinaCorpus\CoreBus\EventBus\EventBusAwareTrait;

final class SayHelloHandler implements EventBusAware
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
namespace App\Domain\SomeBusiness\Listener;

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

Using Symfony container machinery, no configuration is needed for this to work.

# Symfony commands

## Push a message into the bus

Pushing a message is as simple as:

```sh
bin/console corebus:push CommandName <<'EOT'
{
    "message": "contents"
}
EOT
```

## Run worker process

Running the worker process is as simple as:

```sh
bin/console corebus:worker -v
```

If you set `-vv` you will obtain a very verbose output and is a very bad idea
to do in any other environment than your development machine.

Running using `-v` will output a single line for every message being consumed
including some time and memory information. Exceptions traces when a message fail
will be displayed fully in output. This is a good setting for using it with
`systemd` or a `docker` container that will pipe the output into logs.

Not setting any `-v` flag will be equivalent to `-vv` but output will only
happen in monolog, under the `corebus` channel.

Additionally, you may tweak using the following options:

 - `--limit=X`: only process X messages and die,
 - `--routing-key=QUEUE_NAME`: only process messages in the `QUEUE_NAME` queue,
 - `--memory-limit=128M`: when PHP memory limit exceeds the given limit, die,
   per default the process will use current PHP limit minus 16M, in order to
   avoid PHP memory limit reached errors during message processing,
 - `--memory-leak=512K`: warn in output when a single message consumption
   doesn't free completely memory once finished, with the given threshold,
 - `--sleep-time=X`: wait for X microseconds between two messages when there is
   none left to consume before retrying a bus fetch. This may be ignored by some
   implementations in the future.

# Using attributes

This package comes with an attribute support for annotating commands and events
in order to infer behaviors to the bus. This allows to declare commands or
event behaviour without tainting the domain code.

## Command attributes

 - `#[MakinaCorpus\CoreBus\Attr\NoTransaction]` disables transaction
   handling for the command. Use it wisely.

 - `#[MakinaCorpus\CoreBus\Attr\RoutingKey(name: string)]` allows you to route
   the command via the given *routing key* (or *queue name*). Default when this
   attribute is not specified is `default`.

 - `#[MakinaCorpus\CoreBus\Attr\Async]` forces the command to
   always be dispatched asynchronously.
   Warning, this is not implemented yet, and is an empty shell.

 - `#[MakinaCorpus\CoreBus\Attr\Retry(count: ?int)]` allows the command to
   be retried in case an error happen. First parameter is the number of retries
   allowed, default is `3`.
   Warning, this is not implemented yet, and is an empty shell.

## Domain event attributes.

 - `#[MakinaCorpus\CoreBus\Attr\Aggregate(property: string, type: ?string)]`
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

# Roadmap

 - [ ] Implement profiling decorator for event bus using `makinacorpus/profiling`.
 - [ ] Implement profiling decorator for command bus using `makinacorpus/profiling`.
 - [ ] Allow multiple message brokers to co-exist, one for each queue.
 - [ ] Implement dead letter queue routing.
 - [ ] Create a retry strategy chain for having more than one instance.
 - [ ] Implement retry strategy using our attributes.
 - [ ] Configurable per-exception type retry strategry.
 - [ ] Implement an argument resolver for command handlers and event listeners.
 