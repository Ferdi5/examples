<?php

declare(strict_types=1);

namespace App\Domains\Integrations\Creditsafe\Services;

use App\Domains\Integrations\Integration;

class CreditsafeService
{
    /**
     * @throws ApplicationException
     * @throws ConnectionException
     */
    public function getIntegrationInstance(): Integration
    {
        /** @var Integration $integration */
        $integration = Integration::query()->firstOrNew([
            'name' => 'creditsafe',
        ]);

        if (isset($integration->meta['expires_in'])) {
            $tokenExpirationDate = CarbonImmutable::parse($integration->meta['expires_in']);
        }

        if ((empty($tokenExpirationDate) ||
            CarbonImmutable::parse(CarbonImmutable::now())->min($tokenExpirationDate) === $tokenExpirationDate)
        ) {
            $token = $this->generateAccessToken();
            $this->storeApiToken($integration, $token);
        }

        return $integration;
    }

    /**
     * @throws ApplicationException
     * @throws ConnectionException
     */
    public function getRequest(string $token, string $requestSource): array
    {
        try {
            return Http::retry(3, 100)
                ->withToken($token)
                ->get(config('services.creditsafe.url') . $requestSource)
                ->throw()
                ->json();
        } catch (RequestException $e) {
            $status = $e->response?->status() ?? 500;
            $body = $e->response?->body() ?? $e->getMessage();

            throw new ApplicationException($body, $status);
        }
    }

    private function storeApiToken(Integration $integration, string $token): void
    {
        $meta = $integration->meta;
        $meta['token'] = $token;
        $meta['expires_in'] = CarbonImmutable::now()->addSeconds(3600);
        $integration->meta = $meta;
        $integration->save();
    }

    /**
     * @throws ApplicationException
     * @throws ConnectionException
     */
    private function generateAccessToken(): string
    {
        try {
            $bearerToken = Http::retry(3)->post(config('services.creditsafe.url') . '/authenticate', [
                'username' => config('services.creditsafe.username'),
                'password' => config('services.creditsafe.password'),
            ])->json();

            return $bearerToken['token'];
        } catch (RequestException $e) {
            $status = $e->response?->status() ?? 500;
            $body = $e->response?->body() ?? $e->getMessage();

            throw new ApplicationException($body, $status);
        }
    }
}
