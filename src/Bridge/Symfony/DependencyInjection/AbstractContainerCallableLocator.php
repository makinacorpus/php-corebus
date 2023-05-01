<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\ArgumentResolver\ArgumentResolver;
use MakinaCorpus\ArgumentResolver\DefaultArgumentResolver;
use MakinaCorpus\ArgumentResolver\Context\ArrayResolverContext;
use MakinaCorpus\CoreBus\Cache\CallableReference;
use MakinaCorpus\CoreBus\Cache\CallableReferenceList;
use MakinaCorpus\CoreBus\Cache\RuntimeCallableReferenceList;
use Psr\Container\ContainerInterface;

abstract class AbstractContainerCallableLocator
{
    protected CallableReferenceList $referenceList;
    private ?ArgumentResolver $argumentResolver = null;
    private ?ContainerInterface $container = null;

    /**
     * @param array<string,string>|CallableReferenceList $references
     */
    public function __construct($references, ?ContainerInterface $container = null, ?ArgumentResolver $argumentResolver = null)
    {
        $this->argumentResolver = $argumentResolver ?? new DefaultArgumentResolver();
        $this->container = $container;

        if ($references instanceof CallableReferenceList) {
            $this->referenceList = $references;
        } else if (\is_array($references)) {
            $this->referenceList = new RuntimeCallableReferenceList($this->allowMultiple());
            foreach ($references as $id => $className) {
                $this->referenceList->appendFromClass($className, \is_int($id) ? $className : $id);
            }
        }
    }

    protected abstract function allowMultiple(): bool;

    protected function createCallable(CallableReference $reference): callable
    {
        if (!$this->container) {
            throw new \LogicException("Misinitialized event listener locator.");
        }

        $callable = \Closure::fromCallable([
            $this->container->get($reference->serviceId),
            $reference->methodName
        ]);

        if (!$reference->requiresResolve) {
            return $callable;
        }

        $resolver = $this->argumentResolver;

        return static function (object $command) use ($callable, $reference, $resolver) {
            $arguments = $resolver->getArguments(
                $callable,
                new ArrayResolverContext([
                    $reference->parameterName => $command
                ])
            );
            return $callable(...$arguments);
        };
    }
}
