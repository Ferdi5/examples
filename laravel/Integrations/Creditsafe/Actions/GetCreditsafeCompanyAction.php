<?php

declare(strict_types=1);

namespace App\Domains\Integrations\Creditsafe\Actions;

use App\Domains\Integrations\Creditsafe\Mappers\CreditsafeCompanyMapper;
use App\Domains\Integrations\Creditsafe\Services\CreditsafeService;

final class GetCreditsafeCompanyAction
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
    public function run(string $companyId, string $requestSource): array
    {
        $integration = $this->creditsafeService->getIntegrationInstance();

        $response = $this->creditsafeService->getRequest(
            $integration->meta['token'],
            '/companies/' . $companyId . $requestSource
        );

        if (!count($response['report'])) {
            throw new ApplicationException('Company doesn\'t include a report');
        }

        return $this->creditsafeCompanyMapper->mapCompany($response);
    }
}
