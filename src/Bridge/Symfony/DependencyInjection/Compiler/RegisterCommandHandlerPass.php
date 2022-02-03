<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\CoreBus\Cache\Type\CallableReferenceListPhpDumper;
use MakinaCorpus\CoreBus\Implementation\Type\NullCallableReferenceList;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class RegisterCommandHandlerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('corebus.command.handler.locator.container')) {
            return;
        }

        $dumpedClassName = CallableReferenceListPhpDumper::getDumpedClassName('command');
        $dumpedFileName = CallableReferenceListPhpDumper::getFilename($container->getParameter('kernel.cache_dir'), 'command');

        $dumper = new CallableReferenceListPhpDumper($dumpedFileName, false, false);

        foreach ($container->findTaggedServiceIds('app.handler', true) as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $className = $definition->getClass();

            if (!$container->getReflectionClass($className)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" used for service "%s" cannot be found.', $className, $id));
            }

            // @todo Later, use a service locator instead.
            $definition->setPublic(true);
            $dumper->appendFromClass($className, $id);
        }

        if ($dumper->isEmpty()) {
            $dumper->delete();

            $serviceClassName = NullCallableReferenceList::class;
            $definition = new Definition();
            $definition->setClass($serviceClassName);
            $container->setDefinition($serviceClassName, $definition);
        } else {
            $dumper->dump($dumpedClassName);

            $serviceClassName = CallableReferenceListPhpDumper::getDumpedClassNamespace() . '\\' . $dumpedClassName;
            $definition = new Definition();
            $definition->setClass($serviceClassName);
            $definition->setFile($dumpedFileName);
            $container->setDefinition($serviceClassName, $definition);
        }

        $container
            ->getDefinition('corebus.command.handler.locator.container')
            ->setArguments([new Reference($serviceClassName)])
        ;
    }
}
