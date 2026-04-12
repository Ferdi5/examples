<?php

declare(strict_types=1);

namespace App\Domains\Integrations\Creditsafe\Controllers;

use App\Domains\Integrations\Creditsafe\Actions\GetCreditsafeCompanyAction;
use App\Domains\Integrations\Creditsafe\Actions\SearchCreditsafeCompanyAction;
use App\Domains\Integrations\Creditsafe\Requests\CreditsafeRequest;

final class CreditsafeCompanyController
{
    /**
     * @throws ApplicationException
     * @throws ConnectionException
     */
    public function searchCompany(CreditsafeRequest $request, SearchCreditsafeCompanyAction $action): array
    {
        return $action->run($request->input('requestSource'));
    }

    /**
     * @throws ApplicationException
     * @throws ConnectionException
     */
    public function getCompany(string $companyId, CreditsafeRequest $request, GetCreditsafeCompanyAction $action): array
    {
        return $action->run($companyId, $request->input('requestSource'));
    }
}
