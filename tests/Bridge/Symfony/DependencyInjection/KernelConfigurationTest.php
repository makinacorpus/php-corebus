<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Bridge\Symfony\DependencyInjection;

use Goat\Query\Symfony\GoatQueryBundle;
use MakinaCorpus\AccessControl\Authorization;
use MakinaCorpus\AccessControl\Bridge\Symfony\AccessControlBundle;
use MakinaCorpus\CoreBus\Bridge\EventStore\EventStoreEventBusDecorator;
use MakinaCorpus\CoreBus\Bridge\Symfony\Command\CommandPushCommand;
use MakinaCorpus\CoreBus\Bridge\Symfony\Command\CommandWorkerCommand;
use MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\CoreBusExtension;
use MakinaCorpus\CoreBus\CommandBus\CommandAuthorizationChecker;
use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandConsumer;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\CoreBus\EventBus\EventBus;
use MakinaCorpus\EventStore\EventStore;
use MakinaCorpus\EventStore\Bridge\Symfony\EventStoreBundle;
use MakinaCorpus\MessageBroker\MessageConsumerFactory;
use MakinaCorpus\MessageBroker\Bridge\Symfony\MessageBrokerBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

final class KernelConfigurationTest extends TestCase
{
    private function getContainer(array $parameters = [], array $bundles = [])
    {
        // Code inspired by the SncRedisBundle, all credits to its authors.
        $container = new ContainerBuilder(new ParameterBag($parameters + [
            'kernel.debug'=> false,
            'kernel.bundles' => $bundles,
            'kernel.cache_dir' => \sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => \dirname(__DIR__),
        ]));

        /*
        // OK, we will need this.
        $runnerDefinition = new Definition();
        $runnerDefinition->setClass(Runner::class);
        $runnerDefinition->setSynthetic(true);
        $container->setDefinition('goat.runner.default', $runnerDefinition);
        $container->setAlias(Runner::class, 'goat.runner.default');
         */

        /*
        // And this.
        $serializerDefinition = new Definition();
        $serializerDefinition->setClass(SymfonySerializer::class);
        $serializerDefinition->setSynthetic(true);
        $container->setDefinition('serializer', $serializerDefinition);
        $container->setAlias(SymfonySerializer::class, 'serializer');
         */

        if (\in_array(AccessControlBundle::class, $bundles)) {
            $accessControlDefinition = new Definition();
            $accessControlDefinition->setClass(Authorization::class);
            $accessControlDefinition->setSynthetic(true);
            $container->setDefinition(Authorization::class, $accessControlDefinition);
        }

        if (\in_array(EventStoreBundle::class, $bundles)) {
            $eventStoreDefinition = new Definition();
            $eventStoreDefinition->setClass(EventStore::class);
            $eventStoreDefinition->setSynthetic(true);
            $container->setDefinition('event_store.event_store', $eventStoreDefinition);
            $container->setAlias(EventStore::class, 'event_store.event_store');
        }

        if (\in_array(MessageBrokerBundle::class, $bundles)) {
            $messageConsumerFactoryDefinition = new Definition();
            $messageConsumerFactoryDefinition->setClass(MessageConsumerFactory::class);
            $container->setDefinition('message_broker.consumer_factory', $messageConsumerFactoryDefinition);
            $container->setAlias(MessageBroker::class, 'message_broker.consumer_factory');
        }

        return $container;
    }

    /**
     * Get minimal config required.
     */
    private function getMinimalConfig(): array
    {
        return [
            'command_bus' => [
                'adapter' => 'auto',
            ],
            'transaction' => [
                'adapter' => 'auto',
            ],
            'event_store' => [
                'enabled' => false,
            ],
        ];
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testConfigLoadDefault()
    {
        $config = $this->getMinimalConfig();

        $extension = new CoreBusExtension();
        $extension->load([$config], $container = $this->getContainer());

        // Ensure dispatcher configuration.
        self::assertTrue($container->hasAlias(CommandAuthorizationChecker::class));
        self::assertTrue($container->hasDefinition('corebus.command.authorization_checker'));
        self::assertTrue($container->hasDefinition('corebus.command.bus.asynchronous.authorization'));
        self::assertTrue($container->hasDefinition('corebus.command.bus.synchronous.authorization'));

        self::assertTrue($container->hasAlias(CommandBus::class));
        // @todo self::assertTrue($container->hasAlias('corebus.command.bus.asynchronous'));
        self::assertTrue($container->hasAlias('corebus.command.publisher.asynchronous'));
        self::assertTrue($container->hasAlias('corebus.command.publisher.synchronous'));

        self::assertTrue($container->hasAlias(CommandConsumer::class));
        self::assertTrue($container->hasAlias('corebus.command.consumer'));
        self::assertTrue($container->hasDefinition('corebus.command.consumer.transactional'));

        self::assertTrue($container->hasAlias(SynchronousCommandBus::class));
        // @todo self::assertTrue($container->hasAlias('corebus.command.bus.synchronous'));
        self::assertTrue($container->hasDefinition('corebus.command.publisher.passthrough'));

        self::assertTrue($container->hasAlias(CommandConsumer::class));
        self::assertTrue($container->hasDefinition('corebus.command.consumer.default'));

        self::assertTrue($container->hasAlias(EventBus::class));
        self::assertTrue($container->hasAlias('corebus.event.bus.internal'));

        // Some services should not be loaded.
        self::assertFalse($container->hasDefinition('corebus.event_store_info_extractor'));
        self::assertFalse($container->hasDefinition('corebus.event_store_info_extractor.attribute'));
        self::assertFalse($container->hasDefinition(EventStoreEventBusDecorator::class));

        // Console commands.
        self::assertTrue($container->hasDefinition(CommandPushCommand::class));
        self::assertTrue($container->hasDefinition(CommandWorkerCommand::class));

        $container->compile();
    }

    /**
     * Test config with access control.
     */
    public function testConfigLoadWithAccessControlEnabled()
    {
        $config = $this->getMinimalConfig();

        $extension = new CoreBusExtension();
        $extension->load([$config], $container = $this->getContainer([], [
            AccessControlBundle::class,
        ]));

        self::assertTrue($container->hasDefinition('corebus.command.authorization_checker.access_control'));

        $container->compile();
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testConfigLoadWithEventStoreEnabled()
    {
        $config = [
            'event_store' => [
                'enabled' => true,
            ],
        ] + $this->getMinimalConfig();

        $extension = new CoreBusExtension();
        $extension->load([$config], $container = $this->getContainer([], [
            EventStoreBundle::class,
        ]));

        self::assertTrue($container->hasDefinition('corebus.event_store_info_extractor'));
        self::assertTrue($container->hasDefinition('corebus.event_store_info_extractor.attribute'));
        self::assertTrue($container->hasDefinition(EventStoreEventBusDecorator::class));

        $container->compile();
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testConfigLoadWithEventStoreDisabled()
    {
        $config = $this->getMinimalConfig();

        $extension = new CoreBusExtension();
        $extension->load([$config], $container = $this->getContainer([], [
            EventStoreBundle::class,
        ]));

        self::assertFalse($container->hasDefinition('corebus.event_store_info_extractor'));
        self::assertFalse($container->hasDefinition('corebus.event_store_info_extractor.attribute'));
        self::assertFalse($container->hasDefinition(EventStoreEventBusDecorator::class));

        $container->compile();
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testConfigLoadWithMessageBroker()
    {
        $config = [
            'command_bus' => [
                'adapter' => 'message-broker',
            ],
        ] + $this->getMinimalConfig();

        $extension = new CoreBusExtension();
        $extension->load([$config], $container = $this->getContainer([], [
            MessageBrokerBundle::class,
        ]));

        self::assertTrue($container->hasDefinition('corebus.command.bus.message_broker'));
        self::assertSame('corebus.command.bus.message_broker', (string) $container->getAlias('corebus.command.bus.asynchronous'));

        $container->compile();
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testTaggedServicesConfigLoadWithMessageBrokerAuto()
    {
        $config = $this->getMinimalConfig();

        $extension = new CoreBusExtension();
        $extension->load([$config], $container = $this->getContainer([], [
            MessageBrokerBundle::class,
        ]));

        self::assertTrue($container->hasDefinition('corebus.command.bus.message_broker'));
        self::assertSame('corebus.command.bus.message_broker', (string) $container->getAlias('corebus.command.bus.asynchronous'));

        $container->compile();
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testConfigLoadRetryStrategy()
    {
        $config = [
            'command_bus' => [
                'adapter' => 'message-broker',
                'retry_strategy' => [
                    'enabled' => true,
                ],
            ],
        ] + $this->getMinimalConfig();

        $extension = new CoreBusExtension();
        $extension->load([$config], $container = $this->getContainer([], [
            MessageBrokerBundle::class,
        ]));

        self::assertSame('corebus.command.bus.message_broker', (string) $container->getAlias('corebus.command.bus.asynchronous'));
        self::assertTrue($container->hasAlias('corebus.retry_strategy'));
        self::assertTrue($container->hasDefinition('corebus.command.bus.message_broker'));
        self::assertTrue($container->hasDefinition('corebus.retry_strategy.default'));

        $container->compile();
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testConfigLoadRetryStrategyFailWithoutMessageBroker()
    {
        $config = [
            'command_bus' => [
                'adapter' => 'memory',
                'retry_strategy' => [
                    'enabled' => true,
                ],
            ],
        ] + $this->getMinimalConfig();

        $extension = new CoreBusExtension();

        self::expectExceptionMessageMatches('/corebus\.command_bus\.retry_strategy/');
        $extension->load([$config], $this->getContainer([], [
            MessageBrokerBundle::class,
        ]));
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testConfigLoadWithGoatQuery()
    {
        $config = [
            'transaction' => [
                'adapter' => 'goat-query',
            ],
        ] + $this->getMinimalConfig();

        $extension = new CoreBusExtension();
        $extension->load([$config], $container = $this->getContainer([], [
            GoatQueryBundle::class,
        ]));

        self::assertTrue($container->hasDefinition('corebus.transaction.manager.goat_query'));
        self::assertSame('corebus.transaction.manager.goat_query', (string) $container->getAlias('corebus.transaction.manager'));

        $container->compile();
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testConfigLoadWithGoatQueryAuto()
    {
        $config = $this->getMinimalConfig();

        $extension = new CoreBusExtension();
        $extension->load([$config], $container = $this->getContainer([], [
            GoatQueryBundle::class,
        ]));

        self::assertTrue($container->hasDefinition('corebus.transaction.manager.goat_query'));
        self::assertSame('corebus.transaction.manager.goat_query', (string) $container->getAlias('corebus.transaction.manager'));

        $container->compile();
    }
}
