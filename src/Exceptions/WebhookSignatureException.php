<?php

declare(strict_types=1);

namespace Gando\Partner\Exceptions;

class WebhookSignatureException extends \Exception
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }

    public function getReason(): string
    {
        return $this->getMessage();
    }
}
