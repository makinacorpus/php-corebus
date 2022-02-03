<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Cache\Type;

use MakinaCorpus\CoreBus\Implementation\Type\CallableReference;
use MakinaCorpus\CoreBus\Implementation\Type\DefaultCallableReferenceList;
use Symfony\Component\Filesystem\Exception\IOException;

class CallableReferenceListPhpDumper
{
    private string $filename;
    private bool $allowMultiple;
    private bool $withParents;
    private array $references = [];

    public function __construct(string $filename, bool $allowMultiple = false, bool $withParents = false)
    {
        $this->allowMultiple = $allowMultiple;
        $this->filename = $filename;
        $this->withParents = $withParents;
    }

    public static function getFilename(string $kernelCacheDirectory, string $tag): string
    {
        return $kernelCacheDirectory . '/corebus_handler_callback_' . $tag . '.php';
    }

    public static function getDumpedClassNamespace(): string
    {
        return 'MakinaCorpus\CoreBus\Cache\Type\Generated';
    }

    public static function getDumpedClassName(string $tag): string
    {
        return \ucfirst($tag) . 'DumpedCallableReferenceList';
    }

    public function appendFromClass(
        string $handlerClassName,
        ?string $handlerServiceId = null,
        ?string $parameterInterfaceName = null
    ): void {
        foreach (DefaultCallableReferenceList::findHandlerMethods(
            $handlerClassName,
            $handlerServiceId,
            $parameterInterfaceName
        ) as $reference) {
            $this->append($reference);
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->references);
    }

    public function delete(): void
    {
        if (\file_exists($this->filename)) {
            if (!@\unlink($this->filename)) {
                throw new IOException(\sprintf("Could not delete file: %s", $this->filename));
            }
        }
    }

    public function dump(string $dumpedClassName): void
    {
        $this->delete();

        if (!$handle = @\fopen($this->filename, 'cw+')) {
            throw new IOException(\sprintf("Could not open file for writing: %s", $this->filename));
        }

        \fwrite($handle, <<<PHP
<?php

declare(strict_types=1);

namespace MakinaCorpus\CoreBus\Cache\Type\Generated;

use MakinaCorpus\CoreBus\Implementation\Type\CallableReference;
use MakinaCorpus\CoreBus\Implementation\Type\CallableReferenceList;

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

                $escapedHandlerClassName = \addslashes($reference->className);
                $escapedMethodName = \addslashes($reference->methodName);
                $escapedServiceId = \addslashes($reference->serviceId);

                \fwrite($handle, <<<PHP
                    new CallableReference(
                        '{$escapedHandlerClassName}',
                        '{$escapedMethodName}',
                        '{$escapedServiceId}'
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

    private function append(CallableReference $reference): void
    {
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

        $this->references[$reference->className][] = $reference;
    }
}
