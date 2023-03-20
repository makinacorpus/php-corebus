<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Tests\CommandBus;

use MakinaCorpus\CoreBus\CommandBus\Bus\NullCommandBus;
use MakinaCorpus\CoreBus\CommandBus\Error\CommandAlreadySentError;
use MakinaCorpus\CoreBus\CommandBus\Testing\TestingCommandBus;
use MakinaCorpus\Message\Property;
use PHPUnit\Framework\TestCase;

class MessageBuilderTest extends TestCase
{
    public function testReplyToEmptyGeneratesMessageId(): void
    {
        $commandBus = new NullCommandBus();

        $promise = $commandBus
            ->create(new \stdClass())
            ->replyTo(true)
            ->dispatch()
        ;

        $messageId = $promise->getProperties()->get(Property::MESSAGE_ID);
        self::assertNotNull($messageId);
        self::assertSame('corebus.reply-to.' . $messageId, $promise->getProperties()->get(Property::REPLY_TO));
    }

    public function testReplyTo(): void
    {
        $commandBus = new NullCommandBus();

        $promise = $commandBus
            ->create(new \stdClass())
            ->replyTo(true, 'custom_reply_to_queue')
            ->dispatch()
        ;

        self::assertNull($promise->getProperties()->get(Property::MESSAGE_ID));
        self::assertSame('custom_reply_to_queue', $promise->getProperties()->get(Property::REPLY_TO));
    }

    public function testCannotDispatchMoreThanOnce(): void
    {
        $commandBus = new TestingCommandBus();

        $builder = $commandBus->create(new \DateTimeImmutable());
        $builder->dispatch();

        self::expectException(CommandAlreadySentError::class);
        $builder->dispatch();
    }

    public function testDispatch(): void
    {
        $commandBus = new TestingCommandBus();

        $command = new \DateTimeImmutable();
        $commandBus->create($command)->dispatch();

        self::assertSame(1, $commandBus->count());
        self::assertSame($command, $commandBus->first());
    }
}
