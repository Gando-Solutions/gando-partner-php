<?php

declare(strict_types=1);

namespace Gando\Partner\Connect;

final class UrlBuilder
{
    public function __construct(
        private readonly string $connectSecret,
        private readonly string $partnerSlug,
        private readonly string $baseUrl,
    ) {
    }

    public function signupUrl(
        string $externalId,
        ?string $email = null,
        ?string $name = null,
        ?string $returnUrl = null,
        ?int $timestamp = null,
    ): string {
        $ts = (string) ($timestamp ?? time());

        $query = [
            'partner' => $this->partnerSlug,
            'external_id' => $externalId,
            'ts' => $ts,
            'sig' => $this->signature($externalId, $ts),
        ];

        if ($email !== null) {
            $query['email'] = $email;
        }

        if ($name !== null) {
            $query['name'] = $name;
        }

        if ($returnUrl !== null) {
            $query['return_url'] = $returnUrl;
        }

        $base = rtrim($this->baseUrl, '/');

        return $base.'/register?'.http_build_query($query);
    }

    private function signature(string $externalId, string $ts): string
    {
        $payload = $this->signingPayload($externalId, $ts);

        return hash_hmac('sha256', $payload, $this->connectSecret);
    }

    private function signingPayload(string $externalId, string $ts): string
    {
        return $this->partnerSlug.'.'.$externalId.'.'.$ts;
    }
}

