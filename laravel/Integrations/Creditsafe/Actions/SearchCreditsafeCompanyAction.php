<?php

declare(strict_types=1);

namespace App\Domains\Integrations\Creditsafe\Actions;

use App\Domains\Integrations\Creditsafe\Mappers\CreditsafeCompanyMapper;
use App\Domains\Integrations\Creditsafe\Services\CreditsafeService;

final class SearchCreditsafeCompanyAction
{
    public function __construct(
        private readonly CreditsafeCompanyMapper $creditsafeCompanyMapper,
        private readonly CreditsafeService $creditsafeService
    ) {
    }

    /**
     * @throws ApplicationException
     * @throws ConnectionException
     */
    public function run(string $requestSource): array
    {
        $integration = $this->creditsafeService->getIntegrationInstance();

        $response = $this->creditsafeService->getRequest(
            $integration->meta['token'],
            '/companies/' . $requestSource
        );

        if (!count($response['companies'])) {
            throw new ApplicationException($response['messages'][0]['text']);
        }

        return $this->creditsafeCompanyMapper->mapCompanies($response['companies']);
    }
}
