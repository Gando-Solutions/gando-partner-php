<?php

declare(strict_types=1);

namespace Gando\Partner\Api\Events;

use Psr\Http\Message\RequestInterface;

final readonly class HttpRequestFailed
{
    public function __construct(
        public RequestInterface $request,
        public \Throwable $error,
        public int $durationMs,
    ) {
    }
}
