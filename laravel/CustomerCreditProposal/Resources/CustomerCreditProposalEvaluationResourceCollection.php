<?php

declare(strict_types=1);

namespace App\Domains\CustomerCreditProposal\Resources;

final class CustomerCreditProposalEvaluationResourceCollection extends ResourceCollection
{
    /** @var string */
    public $collects = CustomerCreditProposalEvaluationResource::class;

    public function __construct($resource)
    {
        parent::__construct($resource);
    }
}
