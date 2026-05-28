<?php

declare(strict_types=1);

namespace Gando\Partner\Api\Events;

use Psr\Http\Message\RequestInterface;

final readonly class HttpRequestStarted
{
    public function __construct(
        public RequestInterface $request,
    ) {
    }
}
