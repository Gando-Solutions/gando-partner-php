<?php

declare(strict_types=1);

namespace Gando\Partner\Exceptions;

class WebhookSignatureException extends \Exception
{
    public function getReason(): string
    {
        return $this->getMessage();
    }
}
