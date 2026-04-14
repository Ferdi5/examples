<?php

declare(strict_types=1);

namespace App\Domains\CustomerCreditProposal\Repositories;

use App\Domains\CustomerCreditProposal\Models\CustomerCreditProposalEvaluation;

final class CustomerCreditProposalEvaluationRepository
{
    public function __construct(
        protected Company $company
    ) {
    }

    public function fetchOpenEvaluations(string $creditProposalId): Collection {
        return $this->company::query()
            ->where('customer_credit_proposal_id', $creditProposalId)
            ->where(fn (Builder $query) => $query
                ->whereIn('status', [
                    CustomerCreditProposalEvaluation::STATUS_APPROVED,
                    CustomerCreditProposalEvaluation::STATUS_CONDITIONAL,
                    CustomerCreditProposalEvaluation::STATUS_REJECTED,
                ])
                ->orWhereNull('status')
            )
            ->get();
    }
}
