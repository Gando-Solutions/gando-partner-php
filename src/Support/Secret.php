<?php

declare(strict_types=1);

namespace Gando\Support;

/**
 * Opaque credential wrapper — never leaks via __toString(), var_dump(), or __debugInfo().
 *
 * PHP 8.2+ {@see SensitiveParameter} also redacts the constructor argument in stack traces.
 */
final readonly class Secret implements \Stringable
{
    public function __construct(
        #[\SensitiveParameter]
        private string $value,
    ) {}

    public function __toString(): string
    {
        return '***';
    }

    public function reveal(): string
    {
        return $this->value;
    }

    /**
     * @return array{value: string}
     */
    public function __debugInfo(): array
    {
        return ['value' => '***'];
    }
}
