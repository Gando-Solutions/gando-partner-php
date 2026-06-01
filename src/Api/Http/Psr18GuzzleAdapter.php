<?php

declare(strict_types=1);

namespace Gando\Partner\Api\Http;

use Gando\Partner\Api\Events\HttpRequestFailed;
use Gando\Partner\Api\Events\HttpRequestFinished;
use Gando\Partner\Api\Events\HttpRequestStarted;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

final readonly class Psr18GuzzleAdapter implements ClientInterface
{
    public function __construct(
        private Psr18ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private ?LoggerInterface $logger = null,
        private ?EventDispatcherInterface $events = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws GuzzleException
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        $request = $this->applyOptions($request, $options);
        $startedAt = microtime(true);

        $this->logger?->debug('gando.http.request.started', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
        ]);
        $this->events?->dispatch(new HttpRequestStarted($request));

        try {
            $response = $this->httpClient->sendRequest($request);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $this->logger?->debug('gando.http.request.finished', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $durationMs,
            ]);
            $this->events?->dispatch(new HttpRequestFinished($request, $response, $durationMs));

            return $response;
        } catch (\Throwable $throwable) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $this->logger?->error('gando.http.request.failed', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'duration_ms' => $durationMs,
                'error' => $throwable->getMessage(),
            ]);
            $this->events?->dispatch(new HttpRequestFailed($request, $throwable, $durationMs));

            throw new Psr18TransportException($throwable->getMessage(), previous: $throwable);
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        try {
            return Create::promiseFor($this->send($request, $options));
        } catch (\Throwable $throwable) {
            return Create::rejectionFor($throwable);
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        $request = $this->requestFactory->createRequest($method, (string) $uri);

        return $this->send($request, $options);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function requestAsync(string $method, $uri = '', array $options = []): PromiseInterface
    {
        return $this->sendAsync($this->requestFactory->createRequest($method, (string) $uri), $options);
    }

    /**
     * @return mixed
     */
    public function getConfig(?string $option = null)
    {
        return null;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function applyOptions(RequestInterface $request, array $options): RequestInterface
    {
        if (isset($options['query']) && is_array($options['query']) && $options['query'] !== []) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $query);
            $merged = array_merge($query, $options['query']);
            $request = $request->withUri($uri->withQuery(http_build_query($merged)));
        }

        if (isset($options['headers']) && is_array($options['headers'])) {
            foreach ($options['headers'] as $name => $value) {
                if (is_array($value)) {
                    $request = $request->withHeader((string) $name, $value);

                    continue;
                }

                $request = $request->withHeader((string) $name, [(string) $value]);
            }
        }

        if (array_key_exists('body', $options)) {
            $request = $request->withBody($this->normalizeBody($options['body'], $request->getBody()));
        }

        return $request;
    }

    /**
     * @param  mixed  $body
     */
    private function normalizeBody(mixed $body, StreamInterface $fallback): StreamInterface
    {
        if ($body instanceof StreamInterface) {
            return $body;
        }

        if (is_resource($body)) {
            $contents = stream_get_contents($body);

            $contents = $contents === false ? '' : $contents;
            if ($fallback->isSeekable()) {
                $fallback->seek(0);
            }
            $fallback->write($contents);

            return $fallback;
        }

        if (is_scalar($body) || $body === null) {
            if ($fallback->isSeekable()) {
                $fallback->seek(0);
            }
            $fallback->write((string) $body);

            return $fallback;
        }

        $encoded = json_encode($body);
        if ($fallback->isSeekable()) {
            $fallback->seek(0);
        }
        $fallback->write($encoded === false ? '' : $encoded);

        return $fallback;
    }
}
