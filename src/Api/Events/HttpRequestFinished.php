<?php

declare(strict_types=1);

namespace Gando\Partner\Api\Events;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class HttpRequestFinished
{
    public function __construct(
        public RequestInterface $request,
        public ResponseInterface $response,
        public int $durationMs,
    ) {
    }
}
