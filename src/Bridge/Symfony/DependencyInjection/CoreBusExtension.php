<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection;

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

            case 'goat':
                $loader->load('corebus.makinacorpus-goat-adapter.yaml');

                // @todo "true" here is a very wrong default.
                if ($config['adapter_options']['event_store'] ?? true) {
                    $loader->load('corebus.makinacorpus-goat-adapter-eventstore.yaml');
                }
                break;

            default:
                throw new InvalidArgumentException(\sprintf('"corebus.adapter" value "%s" is not supported.'));
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
