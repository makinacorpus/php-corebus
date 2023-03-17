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
        self::assertCount(4, $result);
        self::assertCallableListContains('eligibleMultipleUserTypeMethod', $result, 3);
        self::assertCallableListContains('eligibleUnspecifiedParameterMethod', $result);

        $result = $classParser->lookup(MockHandlerWithAttribute::class);
        $result = \iterator_to_array($result);
        self::assertCount(3, $result);
        self::assertCallableListContains('eligibleUnspecifiedParameterMethod', $result);
        self::assertCallableListContains('eligibleClassParameterMethod', $result);
        self::assertCallableListContains('eligibleInterfaceParameterMethod', $result);
    }

    public function testErrorInvalidUserType(): void
    {
        $classParser = new ClassParser();

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageMatches('/user type .* is not a class or an interface/');
        $result = $classParser->lookup(MockHandlerErrorInvalidUserType::class);
        $result = \iterator_to_array($result);
    }

    public function testErrorMoreThanOneParameter(): void
    {
        $classParser = new ClassParser();

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageMatches('/has more than one parameter/');
        $result = $classParser->lookup(MockHandlerErrorMoreThanOneParameter::class);
        $result = \iterator_to_array($result);
    }

    public function testErrorNoType(): void
    {
        $classParser = new ClassParser();

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageMatches('/first parameter has no type/');
        $result = $classParser->lookup(MockHandlerErrorNoType::class);
        $result = \iterator_to_array($result);
    }

    public function testErrorTargetTwice(): void
    {
        $classParser = new ClassParser();

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageMatches('/target type .* is defined more than once/');
        $result = $classParser->lookup(MockHandlerErrorTargetTwice::class);
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
