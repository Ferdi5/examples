<?php

declare(strict_types=1);

namespace App\Domains\CustomerContract\Controllers;

use App\Domains\CustomerContract\Actions\StoreCustomerContractAction;
use App\Domains\CustomerContract\Requests\StoreCustomerContractRequest;
use App\Domains\CustomerContract\Resources\CustomerContractResource;
use App\Domains\CustomerContract\Transporters\CustomerContractTransporter;

final class CustomerContractController extends Controller
{
    public function store(
        string $salesStatusId,
        StoreCustomerContractRequest $request,
        StoreCustomerContractAction $action
    ): CustomerContractResource {
        return $action->run(
            (new CustomerContractTransporter)
                ->setSalesStatusId($salesStatusId)
                ->setOperatingCompanyId($request->input('contract.operating_company_id'))
                ->setFunderId($request->input('contract.funder_id'))
                ->setCompartmentId($request->input('contract.compartment_id'))
                ->setSignLocation($request->input('contract.sign_location'))
                ->setSignDate($request->input('contract.sign_date'))
                ->setSigned($request->input('contract.signed'))
                ->setAnnexes($request->input('contract.annexes'))
                ->setCompanies($request->input('contract.companies'))
        );
    }
}
