<?php

declare (strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Goat\Dispatcher;

use Goat\Dispatcher\HandlerLocator;
use Goat\Dispatcher\Error\HandlerNotFoundError;
use MakinaCorpus\CoreBus\CommandBus\CommandHandlerLocator;
use MakinaCorpus\CoreBus\CommandBus\Error\CommandHandlerNotFoundError;
use MakinaCorpus\Message\Envelope;

final class HandlerLocaterAdapter implements CommandHandlerLocator
{
    private CommandHandlerLocator $commandHandlerLocator;
    private HandlerLocator $goatHandlerLocator;

    public function __construct(CommandHandlerLocator $commandHandlerLocator, HandlerLocator $goatHandlerLocator)
    {
        $this->commandHandlerLocator = $commandHandlerLocator;
        $this->goatHandlerLocator = $goatHandlerLocator;
    }

    public function find($message): callable
    {
        try {
            return $this->commandHandlerLocator->find($message);

        } catch (CommandHandlerNotFoundError $e) {
            try {
                // This happens because goat.dispatcher may have one to many
                // decorators. In an ideal situation, we should not have any
                // since we implemented everything on our side.
                if ($message instanceof Envelope) {
                    return $this->goatHandlerLocator->find($message->getMessage());
                }

                return $this->goatHandlerLocator->find($message);

            } catch (HandlerNotFoundError $e2) {
                throw new CommandHandlerNotFoundError($e2->getMessage(), $e2->getCode(), $e2);
            }
        }
    }
}
