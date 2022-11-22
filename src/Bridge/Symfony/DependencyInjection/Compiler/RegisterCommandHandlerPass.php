<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\DumpedServiceFactory;
use MakinaCorpus\CoreBus\Cache\Type\CallableReferenceListPhpDumper;
use MakinaCorpus\CoreBus\Implementation\Type\ClassParser;
use MakinaCorpus\CoreBus\Implementation\Type\NullCallableReferenceList;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class RegisterCommandHandlerPass implements CompilerPassInterface
{
    use RegisterClassTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('corebus.command.handler.locator.container')) {
            return;
        }

        $dumper = new CallableReferenceListPhpDumper(
            ClassParser::TARGET_COMMAND_HANDLER,
            false,
            false,
            $container->getParameter('kernel.cache_dir')
        );

        foreach ($container->findTaggedServiceIds('app.handler', true) as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $className = $definition->getClass();

            if (!$container->getReflectionClass($className)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" used for service "%s" cannot be found.', $className, $id));
            }

            // @todo Later, use a service locator instead.
            $definition->setPublic(true);
            $dumper->appendFromClass($className, $id);

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

        $container
            ->getDefinition('corebus.command.handler.locator.container')
            ->setArguments([new Reference($serviceClassName)])
        ;
    }
}
