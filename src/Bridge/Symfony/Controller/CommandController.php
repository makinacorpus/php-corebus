<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\Controller;

use MakinaCorpus\CoreBus\CommandBus\CommandBus;
use MakinaCorpus\CoreBus\CommandBus\SynchronousCommandBus;
use MakinaCorpus\CoreBus\CommandBus\Transaction\MultiCommand;
use MakinaCorpus\MessageBroker\MessageConsumerFactory;
use MakinaCorpus\Normalization\NameMap;
use MakinaCorpus\Normalization\Serializer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * You can try for fun using this URL: /api/v1/dispatch?command=SomeCommandName&async=1&reply-to=1
 *
 * curl -X POST -k -H "Content-Type: application/json" -d '{"entrepriseId":"123456"}' 'https://solipre2.local:8416/api/command/dispatch?command=SP2.Client.Command.EntrepriseRemoteRefreshCommand&async=1&reply_to=1'
 */
final class CommandController
{
    /**
     * Dispatch a multi-command transaction.
     *
     * You need to pass a set of commands, in order, in a JSON array, each
     * array entry is a JSON object containing 'command' (the commande name)
     * and 'body', which is itself a JSON object, which is the command.
     */
    public function dispatchTransaction(
        Serializer $serializer,
        NameMap $nameMap,
        Request $request,
        CommandBus $commandBus,
        SynchronousCommandBus $syncCommandBus
    ): Response {
        if (!$request->isMethod('POST')) {
            throw new MethodNotAllowedHttpException(['POST']);
        }

        $incomingContentType = $request->headers->get('Content-Type');
        // We only accept JSON for now, sorry.
        if ($incomingContentType && 'application/json' !== $incomingContentType) {
            throw new NotAcceptableHttpException('Not Acceptable');
        }

        $data = \json_decode($request->getContent(), true);
        if (!$data || !\is_array($data)) {
            return new Response('Data must be an array of objects, each object must contain a "command" and a "body" value.', Response::HTTP_BAD_REQUEST);
        }

        $commands = [];
        foreach ($data as $key => $commandData) {
            if (!\is_array($commandData) || !isset($commandData['command'])) {
                return new Response(\sprintf('Data must be an array of objects, each object must contain a "command" and a "body" value (item #%s failed).', $key), Response::HTTP_BAD_REQUEST);
            }

            $className = $nameMap->toPhpType($commandData['command'], NameMap::TAG_COMMAND);
            $commands[] = $serializer->unserialize($className, 'application/json', \json_encode($commandData['body'] ?? []));
        }

        return $this->dispatchAndRespond(
            $serializer,
            $commandBus,
            $syncCommandBus,
            $request,
            new MultiCommand($commands)
        );
    }

    /**
     * Dispatch a single command, must be called using POST.
     */
    public function dispatch(
        Serializer $serializer,
        NameMap $nameMap,
        Request $request,
        CommandBus $commandBus,
        SynchronousCommandBus $syncCommandBus
    ): Response {
        if (!$name = $request->get('command')) {
            throw new NotFoundHttpException('Not Found');
        }

        $incomingContentType = $request->headers->get('Content-Type');
        if (!$incomingContentType) {
            // This happens when query is empty, some clients do not bother
            // event writing a Content-Type header (such as Firefox).
            $incomingContentType = 'application/json';
        }

        if ($request->isMethod('GET')) {
            $requestBody = $request->get('body');
        } else {
            $requestBody = $request->getContent();
        }
        if (!$requestBody) {
            // Avoid serializer potential crash.
            $requestBody = '{}';
        }

        $className = $nameMap->toPhpType($name, NameMap::TAG_COMMAND);
        $command = $serializer->unserialize($className, $incomingContentType, $requestBody);

        return $this->dispatchAndRespond(
            $serializer,
            $commandBus,
            $syncCommandBus,
            $request,
            $command
        );
    }

    /**
     * Consume commands from an arbitrary queue.
     */
    public function consume(Request $request, MessageConsumerFactory $messageConsumerFactory): Response
    {
        if (!$name = $request->get('queue')) {
            throw new NotFoundHttpException('Not Found');
        }

        // For security purpose, we only allow custom reply-to queues to be
        // consumed. This may change later.
        // @todo

        $messageConsumer = $messageConsumerFactory->createConsumer([$name]);

        if ($message = $messageConsumer->get()) {
            return new JsonResponse([
                'status' => 'ok',
                'properties' => $message->getProperties(),
                'response' => $message->getMessage(),
            ]);
        }

        return new JsonResponse([
            'status' => 'empty',
        ]);
    }

    /**
     * Handle dispatch and potential response when synchronous.
     */
    private function dispatchAndRespond(
        Serializer $serializer,
        CommandBus $commandBus,
        SynchronousCommandBus $syncCommandBus,
        Request $request,
        object $command
    ): Response {
        if ($request->get('async')) {
            if ($request->get('reply-to')) {
                $promise = $commandBus->create($command)->replyTo(true)->dispatch();
            } else {
                $promise = $commandBus->dispatchCommand($command);
            }

            return new JsonResponse([
                'status' => 'queued',
                'properties' => $promise->getProperties()->all(),
            ]);
        }

        $promise = $syncCommandBus->dispatchCommand($command);

        if (!$promise->isReady()) {
            return new JsonResponse([
                'status' => 'queued',
                'properties' => $promise->getProperties()->all(),
            ]);
        }

        return new JsonResponse(
            [
                'status' => 'ok',
                'properties' => $promise->getProperties()->all(),
                'response' => $promise->get(),
            ],
            200,
            ['Content-Type' => 'application/json']
        );
    }
}
