<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Cache;

use MakinaCorpus\CoreBus\Cache\CallableReference;
use MakinaCorpus\CoreBus\Cache\ClassParser;
use MakinaCorpus\CoreBus\Error\ConfigurationError;
use PHPUnit\Framework\TestCase;

final class ClassParserTest extends TestCase
{
    public function testAll(): void
    {
        $classParser = new ClassParser();

        $result = $classParser->lookup(MockHandler::class);
        $result = \iterator_to_array($result);
        self::assertCount(5, $result);
        self::assertCallableListContains('eligibleMultipleUserTypeMethod', $result, 3);
        self::assertCallableListContains('eligibleWithServiceArgumentInjection', $result);
        self::assertCallableListContains('eligibleWithNamedParameter', $result);

        $result = $classParser->lookup(MockHandlerWithAttribute::class);
        $result = \iterator_to_array($result);
        self::assertCount(2, $result);
        self::assertCallableListContains('eligibleClassParameterMethod', $result);
        self::assertCallableListContains('eligibleInterfaceParameterMethod', $result);
    }

    public function testErrorAmbigousMultipleDeclaration(): void
    {
        $classParser = new ClassParser();

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessage(
            <<<MSG
            Method 'error()' has more than one parameter, target type is not specified, cannot guess which one to use, you must specify at least the target parameter type or name.
            MSG
        );
        $result = $classParser->lookup(MockHandlerErrorAmbigousMultipleDeclaration::class);
        $result = \iterator_to_array($result);
    }

    public function testErrorAmbigousTypeDeclaration(): void
    {
        $classParser = new ClassParser();

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessage(
            <<<MSG
            Method 'error()' has more than one parameter matching the given target type 'DateTimeInterface' ('\$date1' and '\$date2').
            MSG
        );
        $result = $classParser->lookup(MockHandlerErrorAmbigousTypeDeclaration::class);
        $result = \iterator_to_array($result);
    }

    public function testErrorNoType(): void
    {
        $classParser = new ClassParser();

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessage(
            <<<MSG
            Method 'error()' has no class or interface typed parameters, you must specify a target type on the target parameter.
            MSG
        );
        $result = $classParser->lookup(MockHandlerErrorNoType::class);
        $result = \iterator_to_array($result);
    }

    public function testErrorTargetTwice(): void
    {
        $classParser = new ClassParser();

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessage(
            <<<MSG
            Method 'error()' target type 'DateTime' is defined more than once.
            MSG
        );
        $result = $classParser->lookup(MockHandlerErrorTargetTwice::class);
        $result = \iterator_to_array($result);
    }

    public function testErrorUnsupportedUnionType(): void
    {
        $classParser = new ClassParser();

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessage(
            <<<MSG
            Method 'error()' parameter '\$some' has more than one eligible types, using union types are unsupported yet.
            MSG
        );
        $result = $classParser->lookup(MockHandlerErrorUnsupportedUnionType::class);
        $result = \iterator_to_array($result);
    }

    public function testErrorUnsupportedUnionTypeNamed(): void
    {
        $classParser = new ClassParser();

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessage(
            <<<MSG
            Method 'error()' parameter '\$some' has more than one eligible types, using union types is unsupported yet.
            MSG
        );
        $result = $classParser->lookup(MockHandlerErrorUnsupportedUnionTypeNamed::class);
        $result = \iterator_to_array($result);
    }

    private static function assertCallableListContains(string $expected, array $actual, int $count = 1)
    {
        $found = 0;

        foreach ($actual as $index => $object) {
            if (!$object instanceof CallableReference) {
                parent::fail(\sprintf("Callable reference item #%s is not an instance of '%s'", $index, $expected));
            }

            if ($object->methodName === $expected) {
                $found++;
            }
        }

        self::assertSame($count, $found, \sprintf("Callable reference list should contain %d occurences of the method '%s'", $count, $expected));
    }
}
