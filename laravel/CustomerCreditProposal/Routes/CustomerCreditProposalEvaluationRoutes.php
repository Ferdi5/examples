<?php

declare(strict_types=1);

namespace App\Domains\CustomerCreditProposal\Routes;

use App\Domains\CustomerCreditProposal\Controllers\CustomerCreditProposalEvaluationController;

final class CustomerCreditProposalEvaluationRoutes
{
    public static function route(): void
    {
        Route::group(['prefix' => 'credit_proposal'], function () {
            // other routes
            Route::group(['prefix' => '/{creditProposalId}/'], function () {
                Route::put(
                    'submit_evaluations',
                    [CustomerCreditProposalEvaluationController::class, 'submitEvaluations']
                );
            });
        });
    }
}
