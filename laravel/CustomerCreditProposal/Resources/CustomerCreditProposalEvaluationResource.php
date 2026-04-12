<?php

declare(strict_types=1);

namespace App\Domains\CustomerCreditProposal\Resources;

use App\Domains\CustomerCreditProposal\Models\CustomerCreditProposalEvaluation;

/** @property CustomerCreditProposalEvaluation $resource */
final class CustomerCreditProposalEvaluationResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);

        Assert::isInstanceOf($resource, CustomerCreditProposalEvaluation::class);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'customer_credit_proposal_id' => $this->resource->customer_credit_proposal_id,
            'user_id' => $this->resource->user_id,
            'status' => $this->resource->status,
            'message' => $this->resource->message,
            'owner' => $this->resource->owner,
            'comments' => $this->whenLoaded('comments'),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
