<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\PHPUnit;

use MakinaCorpus\ArgumentResolver\ArgumentResolver;
use MakinaCorpus\ArgumentResolver\DefaultArgumentResolver;
use MakinaCorpus\ArgumentResolver\Context\ArrayResolverContext;
use MakinaCorpus\ArgumentResolver\Context\ResolverContext;
use MakinaCorpus\ArgumentResolver\Metadata\ArgumentMetadata;
use MakinaCorpus\ArgumentResolver\Resolver\ArgumentValueResolver;
use MakinaCorpus\ArgumentResolver\Resolver\ContextArgumentValueResolver;
use MakinaCorpus\ArgumentResolver\Resolver\DefaultArgumentValueResolver;
use MakinaCorpus\ArgumentResolver\Resolver\ServiceArgumentValueResolver;
use MakinaCorpus\CoreBus\CommandBus\CommandBusAware;
use MakinaCorpus\CoreBus\CommandBus\CommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Bus\ContainerCommandHandlerLocator;
use MakinaCorpus\CoreBus\CommandBus\Consumer\DefaultCommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\Response\SynchronousCommandResponsePromise;
use MakinaCorpus\CoreBus\CommandBus\Testing\TestingCommandBus;
use MakinaCorpus\CoreBus\EventBus\EventBusAware;
use MakinaCorpus\CoreBus\EventBus\Testing\TestingEventBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class BusAwareTestCase extends TestCase
{
    private ?TestingCommandBus $commandBus = null;
    private ?TestingEventBus $eventBus = null;
    private array $services = [];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->commandBus = new TestingCommandBus();
        $this->eventBus = new TestingEventBus();
        $this->services = [];
    }

    /**
     * Get testing command bus.
     */
    protected function getCommandBus(): TestingCommandBus
    {
        if (!$this->commandBus) {
            throw new \LogicException(\sprintf("Testing command bus is uninitialized, did you forgot to run %s::setUp() ?", __CLASS__));
        }
        return $this->commandBus;
    }

    /**
     * Get testing event bus.
     */
    protected function getEventBus(): TestingEventBus
    {
        if (!$this->eventBus) {
            throw new \LogicException(\sprintf("Testing event bus is uninitialized, did you forgot to run %s::setUp() ?", __CLASS__));
        }
        return $this->eventBus;
    }

    /**
     * Parepare any command bus or event bus aware object.
     */
    protected function prepareObject(object $object): void
    {
        if ($object instanceof CommandBusAware) {
            $object->setCommandBus($this->getCommandBus());
        }
        if ($object instanceof EventBusAware) {
            $object->setEventBus($this->getEventBus());
        }
    }

    /**
     * Create argument resolver.
     */
    private function createArgumentResolver(?array $additionalServices = null, bool $automaticMockObjects = false): ArgumentResolver
    {
        $container = new Container();
        foreach ($this->services as $id => $object) {
            $container->set($id, $object);
        }
        if ($additionalServices) {
            foreach ($additionalServices as $id => $object) {
                $container->set($id, $object);
                $container->set(\get_class($object), $object);
            }
        }

        $argumentValueResolvers = [
            new ContextArgumentValueResolver(),
            new ServiceArgumentValueResolver($container),
            new DefaultArgumentValueResolver(),
        ];

        // Special case for services instanciation, but will not be activated
        // for command handler or event listener methods.
        if ($automaticMockObjects) {
            $factory = fn (string $className) => $this->createMock($className);

            /**
             * Argument value resolver that uses this test case instance mock
             * builder to create missing parameters.
             */
            $argumentValueResolvers[] = new class ($factory) implements ArgumentValueResolver
            {
                private /* callable */ $factory;

                public function __construct(callable $factory)
                {
                    $this->factory = $factory;
                }

                public function supports(ArgumentMetadata $argument, ResolverContext $context): bool
                {
                    foreach ($argument->getTypes() as $type) {
                        if (\class_exists($type) || \interface_exists($type)) {
                            return true;
                        }
                    }
                    return false;
                }

                public function resolve(ArgumentMetadata $argument, ResolverContext $context): iterable
                {
                    foreach ($argument->getTypes() as $type) {
                        if (\class_exists($type) || \interface_exists($type)) {
                            yield ($this->factory)($type);
                            break;
                        }
                    }
                }
            };
        }

        return new DefaultArgumentResolver(
            null,
            $argumentValueResolvers
        );
    }

    /**
     * Inject services into given object.
     */
    private function createObject(string $className, ArgumentResolver $argumentResolver, ResolverContext $context): object
    {
        $refClass = new \ReflectionClass($className);
        $refMethod = $refClass->getConstructor();
        if ($refMethod) {
            if (!$refMethod->isPublic()) {
                throw new \LogicException(\sprintf("Class %s constructor is not public."));
            }

            // We need an instance for having a constructor closure. There is
            // no way around this: the right method would be to allow passing
            // ReflectionMethod instances to the resolver stack.
            $callback = $refMethod->getClosure(
                $refClass->newInstanceWithoutConstructor()
            );

            $object = $refClass->newInstance(
                ...$argumentResolver->getArguments(
                    $callback,
                    $context
                )
            );
        } else {
            $object = $refClass->newInstance();
        }

        $this->prepareObject($object);

        return $object;
    }

    /**
     * For backward compatibility, please do not use this.
     *
     * @deprecated
     */
    protected function createHandler(string $handlerClass, array $params): object
    {
        $services = $this->services;

        foreach ($params as $id => $object) {
            $className = \get_class($object);

            if ($id !== $className) {
                $services[$id] = $object;
            }
            $services[$className] = $object;
        }

        return $this->createObject(
            $handlerClass,
            // Allow filling missing constructor parameters using the test case
            // class mock builder. This will hide some errors but is a useful
            // feature.
            $this->createArgumentResolver($params, true),
            new ArrayResolverContext($services)
        );
    }

    /**
     * Register a service for the test case, it will allow:
     *   - automatic constructor injection for command handlers,
     *   - automatic argument value injection for command handler methods.
     */
    protected function registerService(object $service, string ...$aliases): void
    {
        // Work on a temporary copy in order to avoid polluting the current
        // service list in case of error.
        $services = $this->services;

        $className = \get_class($service);
        if (isset($services[$className])) {
            throw new \LogicException(\sprintf("Service with class %s is already defined.", $className));
        }

        foreach ($aliases as $name) {
            if ($shadowed = ($services[$name] ?? null)) {
                throw new \LogicException(\sprintf("Alias %s for service %s is already in use by service %s.", $name, $className, \get_class($shadowed)));
            }
            $services[$name] = $service;
        }
        $services[$className] = $service;

        $this->services = $services;
    }

    /**
     * Dispatch command inside the command bus.
     */
    protected function consumeCommand(mixed $handler, object $command): CommandResponsePromise
    {
        $argumentResolver = $this->createArgumentResolver();
        $context = new ArrayResolverContext($this->services);

        if (\is_callable($handler)) {
            return SynchronousCommandResponsePromise::success(
                $handler(
                    ...$argumentResolver->getArguments(
                        $handler,
                        $context
                    )
                )
            );
        }

        if (\is_object($handler)) {
            $className = \get_class($handler);
            $object = $handler;

            $container = new Container();
            $container->set($className, $object);

            $handlerLocator = new ContainerCommandHandlerLocator([$className], $container, $argumentResolver);
            $consumer = new DefaultCommandConsumer($handlerLocator);

            return $consumer->consumeCommand($command);
        }

        if (\is_string($handler)) {
            $className = $handler;

            $object = $this->createObject($className);

            $container = new Container();
            $container->set($className, $object);

            $handlerLocator = new ContainerCommandHandlerLocator([$className], $container, $argumentResolver);
            $consumer = new DefaultCommandConsumer($handlerLocator);

            return $consumer->consumeCommand($command);
        }

        throw new \Exception("Not implemented yet.");
    }
}
