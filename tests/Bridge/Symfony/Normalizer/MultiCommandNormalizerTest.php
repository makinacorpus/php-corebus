<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\Bridge\Symfony\Serializer\Normalizer;

use MakinaCorpus\CoreBus\Bridge\Symfony\Serializer\Normalizer\MultiCommandNormalizer;
use MakinaCorpus\CoreBus\CommandBus\Transaction\MultiCommand;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandA;
use MakinaCorpus\CoreBus\Tests\Mock\MockCommandB;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class MultiCommandNormalizerTest extends TestCase
{
    private function createInstance(): MultiCommandNormalizer
    {
        $instance = new MultiCommandNormalizer();

        $instance->setSerializer(new Serializer([
            new ObjectNormalizer(),
        ]));

        return $instance;
    }

    public function testNormalize(): void
    {
        $response = $this->createInstance()->normalize(
            new MultiCommand([
                new MockCommandA(),
                new MockCommandB(),
            ])
        );

        self::assertSame(
            [
                'commands' => [
                    [
                        'type' => MockCommandA::class,
                        'command' => [
                            'done' => false,
                        ],
                    ],
                    [
                        'type' => MockCommandB::class,
                        'command' => [
                            'done' => false,
                        ],
                    ],
                ],
            ],
            $response
        );
    }

    public function testDenormalize(): void
    {
        $data = [
            'commands' => [
                [
                    'type' => MockCommandA::class,
                    'command' => [
                        'done' => false,
                    ],
                ],
                [
                    'type' => MockCommandB::class,
                    'command' => [
                        'done' => true,
                    ],
                ],
            ],
        ];

        $command = $this->createInstance()->denormalize($data, MultiCommand::class);
        self::assertInstanceOf(MultiCommand::class, $command);

        $commands = \iterator_to_array($command);
        self::assertInstanceOf(MockCommandA::class, $commands[0]);
        self::assertFalse($commands[0]->done);
        self::assertInstanceOf(MockCommandB::class, $commands[1]);
        self::assertTrue($commands[1]->done);
    }

    public function testDenormalizeWithMissingCommands(): void
    {
        self::expectExceptionMessageMatches('/because the "commands" property is missing/');

        $this->createInstance()->denormalize([], MultiCommand::class);
    }

    public function testDenormalizeWithMissingType(): void
    {
        self::expectExceptionMessageMatches('/commands "type" property/');

        $this->createInstance()->denormalize(['commands' => [['command' => []]]], MultiCommand::class);
    }

    public function testDenormalizeWithMissingCommandData(): void
    {
        self::expectExceptionMessageMatches('/commands "command" property/');

        $this->createInstance()->denormalize(['commands' => [['type' => MockCommandA::class]]], MultiCommand::class);
    }
}
