<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\ArgumentResolver\Bridge\Symfony\DependencyInjection\Compiler\RegisterArgumentResolverPass;
use MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\DumpedServiceFactory;
use MakinaCorpus\CoreBus\Cache\CallableReference;
use MakinaCorpus\CoreBus\Cache\CallableReferenceListPhpDumper;
use MakinaCorpus\CoreBus\Cache\ClassParser;
use MakinaCorpus\CoreBus\Cache\NullCallableReferenceList;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class RegisterEventListenerPass implements CompilerPassInterface
{
    use RegisterClassTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('corebus.event.listener.locator.container')) {
            return;
        }

        $dumper = new CallableReferenceListPhpDumper(
            ClassParser::TARGET_EVENT_LISTENER,
            true,
            true,
            $container->getParameter('kernel.cache_dir')
        );

        $services = [];
        foreach ($container->findTaggedServiceIds('app.handler', true) as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $className = $definition->getClass();

            if (!$container->getReflectionClass($className)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" used for service "%s" cannot be found.', $className, $id));
            }

            if ($references = $dumper->appendFromClass($className, $id)) {
                $services[$id] = new Reference($id);
                $services[$className] = new Reference($className);

                // Do not break with previous versions of argument-resolver.
                if (\class_exists(RegisterArgumentResolverPass::class)) {
                    $methods = \array_map(fn (CallableReference $reference) => $reference->methodName, $references);
                    RegisterArgumentResolverPass::registerServiceMethods($container, 'corebus', $id, $methods);
                }
            }
            $this->prepareClass($className, $definition);
        }

        if ($dumper->isEmpty()) {
            $dumper->delete();

            $serviceClassName = NullCallableReferenceList::class;
            $definition = new Definition();
            $definition->setClass($serviceClassName);
            $container->setDefinition($serviceClassName, $definition);
        } else {
            $dumper->dump();

            $serviceClassName = $dumper->getDumpedClassName(true);
            $definition = new Definition();
            $definition->setClass($serviceClassName);
            $definition->setFactory([DumpedServiceFactory::class, 'load']);
            $definition->setArguments([$serviceClassName, $dumper->getFilename()]);
            $container->setDefinition($serviceClassName, $definition);
        }

        $definition = $container->getDefinition('corebus.event.listener.locator.container');
        $definition->setArgument(0, new Reference($serviceClassName));
        $definition->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
