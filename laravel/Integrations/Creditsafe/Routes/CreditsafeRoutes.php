<?php

declare(strict_types=1);

namespace App\Domains\Integrations\Creditsafe\Routes;

use App\Domains\Integrations\Creditsafe\Controllers\CreditsafeCompanyController;

class CreditsafeRoutes
{
    public static function route(): void
    {
        Route::prefix('/integrations')->group(
            function () {
                Route::get('/creditsafe/companies', [CreditsafeCompanyController::class, 'searchCompany']);
                Route::get('/creditsafe/companies/{id}', [CreditsafeCompanyController::class, 'getCompany']);
            }
        );

    }
}
