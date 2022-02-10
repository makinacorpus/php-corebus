<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection;

use Goat\Bridge\Symfony\GoatBundle;
use MakinaCorpus\EventStore\Bridge\Symfony\EventStoreBundle;
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

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('corebus.core.yaml');

        switch ($config['adapter']) {

            case 'memory':
                // Fallback configuration, only sync processing will be allowed.
                break;

            case 'goat':
                if (!\in_array(GoatBundle::class, $container->getParameter('kernel.bundles'))) {
                    throw new InvalidArgumentException(\sprintf("corebus.adapter requires makinacorpus/goat to be installed and %s bundle to be enabled when value is 'goat'.", GoatBundle::class));
                }
                $loader->load('corebus.makinacorpus-goat-adapter.yaml');
                break;

            default:
                throw new InvalidArgumentException(\sprintf('"corebus.adapter" value "%s" is not supported.'));
        }

        if ($config['event_store']['enabled'] ?? false) {
            if (!\in_array(EventStoreBundle::class, $container->getParameter('kernel.bundles'))) {
                throw new InvalidArgumentException(\sprintf("corebus.event_store.enabled requires makinacorpus/event-store to be installed and %s bundle to be enabled when value is 'goat'.", EventStoreBundle::class));
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
