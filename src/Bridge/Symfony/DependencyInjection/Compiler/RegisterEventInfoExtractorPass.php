<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\CoreBus\Bridge\Goat\EventStore\EventInfoExtrator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class RegisterEventInfoExtractorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('corebus.event_store_info_extractor')) {
            return;
        }

        $serviceRefList = [];

        foreach ($container->findTaggedServiceIds('app.event_info', true) as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $className = $definition->getClass();

            if (!$reflexion = $container->getReflectionClass($className)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" used for service "%s" cannot be found.', $className, $id));
            }

            if ($reflexion->implementsInterface(EventInfoExtrator::class)) {
                $serviceRefList[] = new Reference($id);
            }
        }

        $container
            ->getDefinition('corebus.event_store_info_extractor')
            ->setArguments([$serviceRefList])
        ;
    }
}
