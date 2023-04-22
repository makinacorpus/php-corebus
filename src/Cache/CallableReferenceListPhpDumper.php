<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Cache;

use Symfony\Component\Filesystem\Exception\IOException;

class CallableReferenceListPhpDumper
{
    const DUMPED_NAMESPACE = 'MakinaCorpus\\CoreBus\\Cache\\Generated';

    private string $target;
    private string $directory;
    private string $filename;
    private bool $allowMultiple;
    private bool $withParents;
    private array $references = [];

    public function __construct(
        string $target,
        bool $allowMultiple = false,
        bool $withParents = false,
        ?string $directory = null
    ) {
        $this->directory = $directory ?? \sys_get_temp_dir() . '/corebus_cache';
        $this->filename = \rtrim($this->directory, '/') . '/corebus_handler_callback_' . $target . '.php';
        $this->target = $target;
        $this->allowMultiple = $allowMultiple;
        $this->withParents = $withParents;
    }

    /**
     * Compute absolute filename.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Compute and get dumped local class name.
     */
    public function getDumpedClassName(bool $fullyQualified = true): string
    {
        $localClassName = \str_replace(' ', '', \ucwords(\str_replace('_', ' ', $this->target))) . 'DumpedCallableReferenceList';
        if ($fullyQualified) {
            return '\\' . self::DUMPED_NAMESPACE . '\\' . $localClassName;
        }
        return $localClassName;
    }

    /**
     * Append items from the given handler class.
     *
     * @return CallableReference[]
     */
    public function appendFromClass(string $handlerClassName, ?string $handlerServiceId = null): array
    {
        $classParser = new ClassParser($this->target);

        $ret = [];
        foreach ($classParser->lookup($handlerClassName) as $reference) {
            $ret[] = $this->append($reference);
        }

        return $ret;
    }

    /**
     * Is this reference list empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->references);
    }

    /**
     * Delete existing file, if any.
     */
    public function delete(): void
    {
        if (\file_exists($this->filename)) {
            if (!@\unlink($this->filename)) {
                throw new IOException(\sprintf("Could not delete file: %s", $this->filename));
            }
        }
    }

    /**
     * Dump file.
     */
    public function dump(): void
    {
        $this->delete();

        if (!$handle = @\fopen($this->filename, 'cw+')) {
            throw new IOException(\sprintf("Could not open file for writing: %s", $this->filename));
        }

        $dumpedNamespace = self::DUMPED_NAMESPACE;
        $dumpedClassName = $this->getDumpedClassName(false);

        \fwrite($handle, <<<PHP
<?php

declare(strict_types=1);

namespace {$dumpedNamespace};

use MakinaCorpus\CoreBus\Cache\CallableReference;
use MakinaCorpus\CoreBus\Cache\CallableReferenceList;

final class {$dumpedClassName} implements CallableReferenceList
{
    public function first(string \$className): ?CallableReference
    {
        return \$this->doFind(\$className)[0] ?? null;
    }

    public function all(string \$className): iterable
    {
        return \$this->doFind(\$className) ?? [];
    }

    private function doFind(string \$className): ?array
    {
PHP
        );

        \fwrite($handle, "\n");

        if ($this->allowMultiple) {
            \fwrite($handle, <<<PHP
        \$candidates = [\$className] + \\class_parents(\$className) + \\class_implements(\$className);

        foreach (\$candidates as \$candidate) switch (\$candidate) {
PHP
            );

        } else {
            \fwrite($handle, <<<PHP
        switch (\$className) {
PHP
            );
        }

        \fwrite($handle, "\n");

        foreach ($this->references as $className => $references) {
            $escapedClassName = \addslashes($className);

            \fwrite($handle, <<<PHP
            case '{$escapedClassName}':
                return [
PHP
            );
            \fwrite($handle, "\n");

            foreach ($references as $reference) {
                \assert($reference instanceof CallableReference);

                $escapedCommandClassName = \addslashes($reference->className);
                $escapedCommandParameterName = \addslashes($reference->parameterName);
                $escapedMethodName = \addslashes($reference->methodName);
                $escapedServiceId = \addslashes($reference->serviceId);
                $escapedRequiresResolve = $reference->requiresResolve ? 'true' : 'false';

                \fwrite($handle, <<<PHP
                    new CallableReference(
                        '{$escapedCommandClassName}',
                        '{$escapedCommandParameterName}',
                        '{$escapedMethodName}',
                        '{$escapedServiceId}',
                        {$escapedRequiresResolve}
                    ),
PHP
                );
                \fwrite($handle, "\n");
            }

            \fwrite($handle, <<<PHP
                ];
PHP
            );
            \fwrite($handle, "\n");
        }

        \fwrite($handle, <<<PHP
            default:
                break;
        }

        return null;
    }
}
PHP
        );
        \fwrite($handle, "\n");
        \fclose($handle);
    }

    /**
     * Prepare, validate and append given reference.
     */
    private function append(CallableReference $reference, ?string $handlerServiceId = null): CallableReference
    {
        if ($handlerServiceId) {
            $reference->serviceId = $handlerServiceId;
        }

        $existing = $this->references[$reference->className][0] ?? null;

        if ($existing && !$this->allowMultiple) {
            \assert($existing instanceof CallableReference);

            throw new \LogicException(\sprintf(
                "Handler for command class %s is already defined using %s::%s, found %s::%s",
                $reference->className,
                $existing->serviceId,
                $existing->methodName,
                $reference->serviceId,
                $reference->methodName
            ));
        }

        return $this->references[$reference->className][] = $reference;
    }
}
