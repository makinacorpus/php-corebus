<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandBusAware;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBusAware;
use MakinaCorpus\CoreBus\EventBus\EventBus;
use MakinaCorpus\CoreBus\EventBus\EventBusAware;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

trait RegisterClassTrait
{
    /**
     * {@inheritdoc}
     */
    protected function prepareClass(string $className, Definition $definition)
    {
        $refClass = new \ReflectionClass($className);

        if ($refClass->implementsInterface(EventBusAware::class)) {
            $definition->addMethodCall('setEventBus', [new Reference(EventBus::class)]);
        }
        if ($refClass->implementsInterface(CommandBusAware::class)) {
            $definition->addMethodCall('setCommandBus', [new Reference(CommandBus::class)]);
        }
        if ($refClass->implementsInterface(SynchronousCommandBusAware::class)) {
            $definition->addMethodCall('setSynchronousCommandBus', [new Reference(SynchronousCommandBus::class)]);
        }
    }
}
