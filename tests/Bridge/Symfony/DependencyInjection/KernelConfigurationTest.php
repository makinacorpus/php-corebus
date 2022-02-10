<?php

declare(strict_types=1);

namespace Goat\Bridge\Symfony\Tests\DependencyInjection;

use Goat\Bridge\Symfony\GoatBundle;
use Goat\Dispatcher\Dispatcher;
use MakinaCorpus\CoreBus\Bridge\EventStore\EventStoreEventBusDecorator;
use MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\CoreBusExtension;
use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\CoreBus\EventBus\EventBus;
use MakinaCorpus\EventStore\EventStore;
use MakinaCorpus\EventStore\Bridge\Symfony\EventStoreBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use MakinaCorpus\CoreBus\Bridge\Goat\Dispatcher\MessageBrokerCommandBus;

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

        if (\in_array(EventStoreBundle::class, $bundles)) {
            $eventStoreDefinition = new Definition();
            $eventStoreDefinition->setClass(EventStore::class);
            $eventStoreDefinition->setSynthetic(true);
            $container->setDefinition('event_store.event_store', $eventStoreDefinition);
            $container->setAlias(EventStore::class, 'event_store.event_store');
        }

        if (\in_array(GoatBundle::class, $bundles)) {
            $goatDispatcherDefinition = new Definition();
            $goatDispatcherDefinition->setClass(Dispatcher::class);
            $container->setDefinition('goat.dispatcher', $goatDispatcherDefinition);
            $container->setAlias(Dispatcher::class, 'goat.dispatcher');
        }

        return $container;
    }

    /**
     * Get minimal config required.
     */
    private function getMinimalConfig(): array
    {
        return [
            'adapter' => 'memory',
        ];
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testTaggedServicesConfigLoad()
    {
        $config = $this->getMinimalConfig();

        $extension = new CoreBusExtension();
        $extension->load([$config], $container = $this->getContainer());

        // Ensure dispatcher configuration.
        self::assertTrue($container->hasAlias('corebus.command.bus.asynchronous'));
        self::assertTrue($container->hasAlias('corebus.command.bus.synchronous'));
        self::assertTrue($container->hasAlias(CommandBus::class));
        self::assertTrue($container->hasAlias(EventBus::class));
        self::assertTrue($container->hasAlias(SynchronousCommandBus::class));
        self::assertTrue($container->hasDefinition('corebus.bus.transactional'));

        // Some services should not be loaded.
        self::assertFalse($container->hasDefinition('corebus.event_store_info_extractor'));
        self::assertFalse($container->hasDefinition('corebus.event_store_info_extractor.attribute'));
        self::assertFalse($container->hasDefinition(EventStoreEventBusDecorator::class));

        $container->compile();
    }

    /**
     * Test default config for resulting tagged services
     */
    public function testTaggedServicesConfigLoadWithEventStoreEnabled()
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
    public function testTaggedServicesConfigLoadWithEventStoreDisabled()
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
    public function testTaggedServicesConfigLoadWithGoat()
    {
        $config = [
            'adapter' => 'goat',
        ] + $this->getMinimalConfig();

        $extension = new CoreBusExtension();
        $extension->load([$config], $container = $this->getContainer([], [
            GoatBundle::class,
        ]));

        // Ensure dispatcher configuration.
        self::assertTrue($container->hasDefinition('corebus.transaction.manager.goat_query'));
        self::assertTrue($container->hasDefinition(MessageBrokerCommandBus::class));

        $container->compile();
    }
}
