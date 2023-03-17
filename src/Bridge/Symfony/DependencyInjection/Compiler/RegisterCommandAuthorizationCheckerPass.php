<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;

final class RegisterCommandAuthorizationCheckerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('corebus.command.authorization_checker')) {
            return;
        }

        $definition = $container->getDefinition('corebus.command.authorization_checker');

        $services = [];
        foreach ($this->findAndSortTaggedServices('corebus.authorization_checker', $container) as $reference) {
            $services[] = $reference;
        }
        if ($services) {
            $definition->setArgument(0, $services);
        }
    }
}
