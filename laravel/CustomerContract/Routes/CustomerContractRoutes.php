<?php

declare(strict_types=1);

namespace App\Domains\CustomerContract\Routes;

use App\Domains\CustomerContract\Controllers\CustomerContractController;

final class CustomerContractRoutes
{
    public static function route(): void
    {
        Route::apiResource('/contract', CustomerContractController::class)->only(['show']);
    }
}
