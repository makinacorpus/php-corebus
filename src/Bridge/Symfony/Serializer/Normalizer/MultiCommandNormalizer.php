<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Bridge\Symfony\Serializer\Normalizer;

use MakinaCorpus\CoreBus\CommandBus\Transaction\MultiCommand;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class MultiCommandNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        // @codeCoverageIgnoreStart
        if (!$object instanceof MultiCommand) {
            throw new UnexpectedValueException();
        }
        if (!$this->serializer instanceof NormalizerInterface) {
            throw new LogicException(\sprintf('Cannot normalize instance of "%s" because the injected serializer is not a normalizer.', MultiCommand::class));
        }
        // @codeCoverageIgnoreEnd

        $data = [];
        foreach ($object as $command) {
            $data['commands'][] = [
                'type' => \get_class($command),
                'command' => $this->serializer->normalize($command, $format, $context),
            ];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof MultiCommand;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        // @codeCoverageIgnoreStart
        if (!$this->serializer instanceof DenormalizerInterface) {
            throw new LogicException(\sprintf('Cannot denormalize instance of "%s" because the injected serializer is not a denormalizer.', MultiCommand::class));
        }
        // @codeCoverageIgnoreEnd
        if (!\is_array($data) || !\is_array($data['commands'] ?? null)) {
            throw new InvalidArgumentException(\sprintf('Cannot denormalize instance of "%s" because the "commands" property is missing.', MultiCommand::class));
        }

        $commands = [];
        foreach ($data['commands'] as $key => $commandData) {
            if (!\is_array($commandData) || !\is_string($commandData['type'] ?? null)) {
                throw new InvalidArgumentException(\sprintf('Cannot denormalize instance of "%s" because the #%s commands "type" property is missing.', MultiCommand::class, $key));
            }
            if (!isset($commandData['command'])) {
                throw new InvalidArgumentException(\sprintf('Cannot denormalize instance of "%s" because the #%s commands "command" property is missing.', MultiCommand::class, $key));
            }

            $commands[] = $this->serializer->denormalize($commandData['command'], $commandData['type'], $format, $context);
        }

        return new MultiCommand($commands);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return MultiCommand::class === $type;
    }
}
