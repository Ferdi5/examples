<?php

declare(strict_types=1);

namespace App\Domains\CustomerContract\Resources;

use App\Domains\CustomerContract\Models\CustomerContract;

/** @property CustomerContract $resource */
final class CustomerContractResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);

        Assert::isInstanceOf($resource, CustomerContract::class);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'operating_company_id' => $this->resource->operating_company_id,
            'funder_id' => $this->resource->funder_id,
            'compartment_id' => $this->resource->compartment_id,
            'type' => $this->resource->type,
            'sign_location' => $this->resource->sign_location,
            'sign_date' => $this->resource->sign_date,
            'signed' => $this->resource->signed,
            'annexes' => new CustomerContractAnnexResourceCollection($this->resource->customerAnnexes),
            'companies' => $this->whenLoaded('companies'),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
