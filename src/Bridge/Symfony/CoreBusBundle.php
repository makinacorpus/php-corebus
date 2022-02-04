<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony;

use MakinaCorpus\CoreBus\Attr\CommandHandler as CommandHandlerAttribute;
use MakinaCorpus\CoreBus\Attr\EventListener as EventListenerAttribute;
use MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\Compiler\RegisterCommandHandlerPass;
use MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\Compiler\RegisterEventInfoExtractorPass;
use MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\Compiler\RegisterEventListenerPass;
use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\CommandBusAware;
use MakinaCorpus\CoreBus\CommandBus\CommandHandler;
use MakinaCorpus\CoreBus\EventBus\EventBus;
use MakinaCorpus\CoreBus\EventBus\EventBusAware;
use MakinaCorpus\CoreBus\EventBus\EventListener;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class CoreBusBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        // @todo Following entries kept for backward compatibility.
        $container->registerForAutoconfiguration(CommandHandler::class)->addTag('app.handler');
        $container->registerForAutoconfiguration(EventListener::class)->addTag('app.handler');

        $container
            ->registerForAutoconfiguration(EventBusAware::class)
            ->addMethodCall('setEventBus', [new Reference(EventBus::class)])
        ;
        $container
            ->registerForAutoconfiguration(CommandBusAware::class)
            ->addMethodCall('setCommandBus', [new Reference(CommandBus::class)])
        ;

        $container->registerAttributeForAutoconfiguration(
            CommandHandlerAttribute::class,
            static function (
                ChildDefinition $definition,
                CommandHandlerAttribute $attribute,
                \ReflectionClass|\ReflectionMethod $reflector
            ): void {
                $definition->addTag('app.handler');
            }
        );

        $container->registerAttributeForAutoconfiguration(
            EventListenerAttribute::class,
            static function (
                ChildDefinition $definition,
                EventListenerAttribute $attribute,
                \ReflectionClass|\ReflectionMethod $reflector
            ): void {
                $definition->addTag('app.handler');
            }
        );

        $container->addCompilerPass(new RegisterCommandHandlerPass());
        $container->addCompilerPass(new RegisterEventListenerPass());
        $container->addCompilerPass(new RegisterEventInfoExtractorPass());
    }
}
