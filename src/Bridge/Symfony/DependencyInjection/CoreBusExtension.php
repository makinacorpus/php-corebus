<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection;

use Goat\Query\Symfony\GoatQueryBundle;
use MakinaCorpus\AccessControl\Bridge\Symfony\AccessControlBundle;
use MakinaCorpus\CoreBus\Bridge\EventStore\EventStoreCommandConsumerDecorator;
use MakinaCorpus\CoreBus\Bridge\EventStore\EventStoreEventBusDecorator;
use MakinaCorpus\EventStore\Bridge\Symfony\EventStoreBundle;
use MakinaCorpus\MessageBroker\Bridge\Symfony\MessageBrokerBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Serializer\Serializer;

/**
 * {@codeCoverageIgnore}
 */
final class CoreBusExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $kernelBundles = $container->getParameter('kernel.bundles');
        $accessControlDetected = \in_array(AccessControlBundle::class, $kernelBundles);
        $eventStoreBundleDetected = \in_array(EventStoreBundle::class, $kernelBundles);
        $goatQueryBundleDetected = \in_array(GoatQueryBundle::class, $kernelBundles);
        $messageBrokerBundleDetected = \in_array(MessageBrokerBundle::class, $kernelBundles);
        $messageBrokerEnabled = false;

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('corebus.core.yaml');
        $loader->load('corebus.console.yaml');

        if ($accessControlDetected && ($config['access_control']['enabled'] ?? false)) {
            $loader->load('corebus.access_control.yaml');
        }

        switch ($config['command_bus']['adapter'] ?? 'auto') {

            case 'auto':
                if ($messageBrokerBundleDetected) {
                    $loader->load('corebus.makinacorpus-message-broker.yaml');
                    $messageBrokerEnabled = true;
                }
                // Fallback configuration, only sync processing will be allowed.
                break;

            case 'memory':
                // Fallback configuration, only sync processing will be allowed.
                break;

            case 'message-broker':
                if (!$messageBrokerBundleDetected) {
                    throw new InvalidArgumentException(\sprintf("'corebus.command_bus.adapter' requires 'makinacorpus/message-broker' to be installed and '%s' bundle to be enabled when value is 'message-broker'.", MessageBrokerBundle::class));
                }
                $loader->load('corebus.makinacorpus-message-broker.yaml');
                $messageBrokerEnabled = true;
                break;

            default:
                throw new InvalidArgumentException(\sprintf("'corebus.command_bus.adapter' value '%s' is not supported.", $config['command_bus']['adapter']));
        }

        switch ($config['transaction']['adapter'] ?? 'auto') {

            case 'auto':
                if ($goatQueryBundleDetected) {
                    $loader->load('corebus.makinacorpus-goat-query.yaml');
                }
                // Fallback configuration, no transaction support.
                break;

            case 'none':
                // Fallback configuration, no transaction support.
                break;

            case 'goat-query':
                if (!$goatQueryBundleDetected) {
                    throw new InvalidArgumentException(\sprintf("'corebus.transaction.adapter' requires 'makinacorpus/goat-query-bundle' to be installed and %s bundle to be enabled when value is 'goat-query'.", GoatQueryBundle::class));
                }
                $loader->load('corebus.makinacorpus-goat-query.yaml');
                break;

            default:
                throw new InvalidArgumentException(\sprintf("'corebus.command_bus.adapter' value '%s' is not supported.", $config['command_bus']['adapter']));
        }

        if ($config['command_bus']['retry_strategy']['enabled'] ?? false) {
            if (!$messageBrokerEnabled) {
                throw new InvalidArgumentException(\sprintf("'corebus.command_bus.retry_strategy.enabled' requires that 'command_bus.adapter' is set to 'message_broker'.", EventStoreBundle::class));
            }
            $loader->load('corebus.retry_strategy.yaml');

            $definition = $container->getDefinition('corebus.retry_strategy.default');
            $definition->setArguments([
                $config['command_bus']['retry_strategy']['retry_on_database_failure'] ?? true,
                $config['command_bus']['retry_strategy']['retry_count'] ?? 3,
            ]);
        }

        if ($config['event_store']['enabled'] ?? false) {
            if (!$eventStoreBundleDetected) {
                throw new InvalidArgumentException(\sprintf("'corebus.event_store.enabled' requires 'makinacorpus/event-store' to be installed and %s bundle to be enabled.", EventStoreBundle::class));
            }
            $loader->load('corebus.makinacorpus-eventstore-adapter.yaml');

            $runtimeProjectorEnabled = $config['event_store']['runtime_projector'] ?? false;

            if ($runtimeProjectorEnabled) {
                $container
                    ->getDefinition(EventStoreEventBusDecorator::class)
                    ->setArgument(3, new Reference('@?event_store.projector.player'))
                ;
            }

            if ($config['event_store']['log_commands'] ?? false) {
                $loader->load('corebus.makinacorpus-eventstore-command-adapter.yaml');

                if ($runtimeProjectorEnabled) {
                    $container
                        ->getDefinition(EventStoreCommandConsumerDecorator::class)
                        ->setArgument(3, new Reference('@?event_store.projector.player'))
                    ;
                }
            }
        }

        // @todo Make this configurable
        if (\class_exists(Serializer::class)) {
            $loader->load('corebus.symfony.serializer.yaml');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new CoreBusConfiguration();
    }
}
