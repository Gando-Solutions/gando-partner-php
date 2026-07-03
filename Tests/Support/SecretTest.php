<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Support;

use Gando\Support\Secret;
use PHPUnit\Framework\TestCase;

final class SecretTest extends TestCase
{
    private const PLAIN = 'gando_pk_test_secret_value';

    public function test_to_string_masks_value(): void
    {
        $secret = new Secret(self::PLAIN);

        self::assertSame('***', (string) $secret);
    }

    public function test_reveal_returns_plain_value(): void
    {
        $secret = new Secret(self::PLAIN);

        self::assertSame(self::PLAIN, $secret->reveal());
    }

    public function test_debug_info_masks_value(): void
    {
        $secret = new Secret(self::PLAIN);

        self::assertSame(['value' => '***'], $secret->__debugInfo());
    }

    public function test_var_dump_does_not_leak_plain_value(): void
    {
        $secret = new Secret(self::PLAIN);

        ob_start();
        var_dump($secret);
        $output = (string) ob_get_clean();

        self::assertStringNotContainsString(self::PLAIN, $output);
        self::assertStringContainsString('***', $output);
    }

    public function test_stack_trace_does_not_contain_sensitive_constructor_argument(): void
    {
        $plain = 'gando_cs_constructor_trace_secret';

        try {
            $this->invokeSensitiveCallable($plain);
        } catch (\RuntimeException $exception) {
            $serializedTrace = $exception->getTraceAsString();

            self::assertStringNotContainsString($plain, $serializedTrace);
        }
    }

    private function invokeSensitiveCallable(#[\SensitiveParameter] string $value): void
    {
        new Secret($value);

        throw new \RuntimeException('trace probe');
    }
}
