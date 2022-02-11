<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection;

use Goat\Query\Symfony\GoatQueryBundle;
use MakinaCorpus\EventStore\Bridge\Symfony\EventStoreBundle;
use MakinaCorpus\MessageBroker\Bridge\Symfony\MessageBrokerBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
        $eventStoreBundleDetected = \in_array(EventStoreBundle::class, $kernelBundles);
        $goatQueryBundleDetected = \in_array(GoatQueryBundle::class, $kernelBundles);
        $messageBrokerBundleDetected = \in_array(MessageBrokerBundle::class, $kernelBundles);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('corebus.core.yaml');

        switch ($config['command_bus']['adapter'] ?? 'auto') {

            case 'auto':
                if ($messageBrokerBundleDetected) {
                    $loader->load('corebus.makinacorpus-message-broker.yaml');
                }
                // Fallback configuration, only sync processing will be allowed.
                break;

            case 'memory':
                // Fallback configuration, only sync processing will be allowed.
                break;

            case 'message-broker':
                if (!$messageBrokerBundleDetected) {
                    throw new InvalidArgumentException(\sprintf("corebus.command_bus.adapter requires makinacorpus/message-broker to be installed and %s bundle to be enabled when value is 'message-broker'.", MessageBrokerBundle::class));
                }
                $loader->load('corebus.makinacorpus-message-broker.yaml');
                break;

            default:
                throw new InvalidArgumentException(\sprintf("corebus.command_bus.adapter value '%s' is not supported.", $config['command_bus']['adapter']));
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
                    throw new InvalidArgumentException(\sprintf("corebus.transaction.adapter requires makinacorpus/goat-query-bundle to be installed and %s bundle to be enabled when value is 'goat-query'.", GoatQueryBundle::class));
                }
                $loader->load('corebus.makinacorpus-goat-query.yaml');
                break;

            default:
                throw new InvalidArgumentException(\sprintf("corebus.command_bus.adapter value '%s' is not supported.", $config['command_bus']['adapter']));
        }

        if ($config['event_store']['enabled'] ?? false) {
            if (!$eventStoreBundleDetected) {
                throw new InvalidArgumentException(\sprintf("corebus.event_store.enabled requires makinacorpus/event-store to be installed and %s bundle to be enabled.", EventStoreBundle::class));
            }
            $loader->load('corebus.makinacorpus-eventstore-adapter.yaml');
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
