<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\CoreBus\Cache\Type\CallableReferenceListPhpDumper;
use MakinaCorpus\CoreBus\EventBus\EventListener;
use MakinaCorpus\CoreBus\Implementation\Type\NullCallableReferenceList;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class RegisterEventListenerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $dumpedClassName = CallableReferenceListPhpDumper::getDumpedClassName('event');
        $dumpedFileName = CallableReferenceListPhpDumper::getFilename($container->getParameter('kernel.cache_dir'), 'event');

        $dumper = new CallableReferenceListPhpDumper($dumpedFileName, true, true);

        foreach ($container->findTaggedServiceIds('app.handler', true) as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $className = $definition->getClass();

            if (!$reflexion = $container->getReflectionClass($className)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" used for service "%s" cannot be found.', $className, $id));
            }

            if ($reflexion->implementsInterface(EventListener::class)) {
                $definition->setPublic(true);
                $dumper->appendFromClass($className, $id);
            }
        }

        if ($dumper->isEmpty()) {
            $dumper->delete();

            $serviceClassName = NullCallableReferenceList::class;
            $definition = new Definition();
            $definition->setClass($serviceClassName);
            $definition->setPrivate(true);
            $container->setDefinition($serviceClassName, $definition);
        } else {
            $dumper->dump($dumpedClassName);

            $serviceClassName = CallableReferenceListPhpDumper::getDumpedClassNamespace() . '\\' . $dumpedClassName;
            $definition = new Definition();
            $definition->setClass($serviceClassName);
            $definition->setFile($dumpedFileName);
            $definition->setPrivate(true);
            $container->setDefinition($serviceClassName, $definition);
        }

        $container
            ->getDefinition('corebus.event.listener.locator.container')
            ->setArguments([new Reference($serviceClassName)])
        ;
    }
}
