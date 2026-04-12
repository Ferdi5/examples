<?php

declare(strict_types=1);

namespace App\Domains\CustomerCreditProposal\Controllers;

use App\Domains\CustomerCreditProposal\Actions\SubmitCreditProposalEvaluationsAction;
use App\Domains\CustomerCreditProposal\Requests\SubmitCreditProposalEvaluationsRequest;
use App\Domains\CustomerCreditProposal\Resources\CustomerCreditProposalResource;
use App\Domains\CustomerCreditProposal\Transporters\CustomerCreditProposalEvaluationTransporter;

final class CustomerCreditProposalEvaluationController extends Controller
{
    public function submitEvaluations(
        string $salesStatusId,
        string $creditProposalId,
        SubmitCreditProposalEvaluationsRequest $request,
        SubmitCreditProposalEvaluationsAction $action
    ): CustomerCreditProposalResource {
        return $action->run(
            (new CustomerCreditProposalEvaluationTransporter)
                ->setSalesStatusId($salesStatusId)
                ->setCreditProposalId($creditProposalId)
                ->setCurrentUserId($this->user->id)
                ->setRevisionData($request->input('revision_data'))
        );
    }
}
