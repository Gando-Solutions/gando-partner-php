<?php

declare(strict_types=1);

namespace Gando\Partner\Api\Http;

use GuzzleHttp\Exception\GuzzleException;

final class Psr18TransportException extends \RuntimeException implements GuzzleException
{
}
