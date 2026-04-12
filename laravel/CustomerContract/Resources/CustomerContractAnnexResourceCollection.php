<?php

declare(strict_types=1);

namespace App\Domains\CustomerContract\Resources;

final class CustomerContractAnnexResourceCollection extends ResourceCollection
{
    /** @var string */
    public $collects = CustomerContractAnnexResource::class;

    public function __construct($resource)
    {
        parent::__construct($resource);
    }
}
