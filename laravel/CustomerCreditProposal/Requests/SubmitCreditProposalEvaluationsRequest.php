<?php

declare(strict_types=1);

namespace App\Domains\CustomerCreditProposal\Requests;

use App\Domains\CustomerCreditProposal\Models\CustomerCreditProposalEvaluation;

class SubmitCreditProposalEvaluationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_status_id' => 'required|numeric|exists:sales_status,id|exists:customer_credit_proposal,sales_status_id',
            'credit_proposal_id' => 'required|numeric|exists:customer_credit_proposal,id|exists:customer_credit_proposal_evaluation,customer_credit_proposal_id',
            'revision_data' => 'nullable|string|' . Rule::requiredIf(function (): bool {
                $evaluations = CustomerCreditProposalEvaluation::query()
                    ->where('customer_credit_proposal_id', $this->input('credit_proposal_id'))
                    ->where(function (Builder $query): void {
                        $query->whereIn(
                            'status',
                            [
                                CustomerCreditProposalEvaluation::STATUS_APPROVED,
                                CustomerCreditProposalEvaluation::STATUS_CONDITIONAL,
                                CustomerCreditProposalEvaluation::STATUS_REJECTED,
                            ]
                        )
                            ->orWhereNull('status');
                    })
                    ->get();

                $approvedEvaluations = $evaluations->filter(
                    function (CustomerCreditProposalEvaluation $evaluation): bool {
                        return $evaluation->status === CustomerCreditProposalEvaluation::STATUS_APPROVED;
                    }
                );

                if (count($approvedEvaluations) === count($evaluations)) {
                    return true;
                }

                return false;
            }),
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'sales_status_id' => $this->route('salesStatusId'),
            'credit_proposal_id' => $this->route('creditProposalId'),
        ]);
    }
}
