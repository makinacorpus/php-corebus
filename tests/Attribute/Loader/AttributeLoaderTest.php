<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Attribute\Loader;

use MakinaCorpus\CoreBus\Attribute\Attribute;
use MakinaCorpus\CoreBus\Attribute\AttributeLoader;
use MakinaCorpus\CoreBus\Attribute\Error\AttributeError;
use PHPUnit\Framework\TestCase;

class AttributeLoaderTest extends TestCase
{
    public function testLoadFromClass(): void
    {
        $loader = $this->createAttributeLoader();

        $count = 0;
        foreach ($loader->loadFromClass(AttributeLoaderMock::class) as $policy) {
            self::assertInstanceOf(Attribute::class, $policy);
            $count++;
        }

        self::assertSame(3, $count);
    }

    public function testLoadFromClassErrorWhenClassDoesNotExist(): void
    {
        $loader = $this->createAttributeLoader();

        self::expectException(AttributeError::class);
        self::expectExceptionMessageMatches('/Class does not exist/');

        foreach ($loader->loadFromClass('NonExistingClass') as $class) {
            self::fail();
        }
    }

    public function testLoadFromClassMethod(): void
    {
        $loader = $this->createAttributeLoader();

        $count = 0;
        foreach ($loader->loadFromClassMethod(AttributeLoaderMock::class, 'normalMethod') as $policy) {
            self::assertInstanceOf(Attribute::class, $policy);
            $count++;
        }

        self::assertSame(3, $count);
    }

    public function testLoadFromClassMethodErrorWhenClassDoesNotExist(): void
    {
        $loader = $this->createAttributeLoader();

        self::expectException(AttributeError::class);
        self::expectExceptionMessageMatches('/Class does not exist/');

        foreach ($loader->loadFromClassMethod('NonExistingClass', 'nonExistingMethod') as $class) {
            self::fail();
        }
    }

    public function testLoadFromClassMethodErrorWhenMethodDoesNotExist(): void
    {
        $loader = $this->createAttributeLoader();

        self::expectException(AttributeError::class);
        self::expectExceptionMessageMatches('/Class method does not exist/');

        foreach ($loader->loadFromClassMethod(AttributeLoaderMock::class, 'nonExistingMethod') as $class) {
            self::fail();
        }
    }

    public function testLoadFromFunction(): void
    {
        self::markTestIncomplete();
    }

    private function createAttributeLoader(): AttributeLoader
    {
        return new AttributeLoader();
    }
}
