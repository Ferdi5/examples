<?php

declare(strict_types=1);

namespace App\Domains\CustomerContract\Actions;

use App\Domains\CustomerContract\Models\CustomerContract;
use App\Domains\CustomerContract\Models\CustomerContractAnnex;
use App\Domains\CustomerContract\Resources\CustomerContractResource;
use App\Domains\CustomerContract\Tasks\ListContractAnnexCertaintyTask;
use App\Domains\CustomerContract\Tasks\ListContractAnnexRequiredDocumentTask;
use App\Domains\CustomerContract\Transporters\CustomerContractTransporter;

final class StoreCustomerContractAction extends Action
{
    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly ListCertaintiesAction $listCertaintiesAction,
        private readonly ListRequiredDocumentsAction $listRequiredDocumentsAction
    ) {
    }

    public function run(
        CustomerContractTransporter $customerContractTransporter,
    ): CustomerContractResource {
        return DB::transaction(function () use ($customerContractTransporter) {
            /** @var CustomerContract $contract */
            $contract = CustomerContract::query()->create([
                'sales_status_id' => $customerContractTransporter->getSalesStatusId(),
                'operating_company_id' => $customerContractTransporter->getOperatingCompanyId(),
                'funder_id' => $customerContractTransporter->getFunderId(),
                'compartment_id' => $customerContractTransporter->getCompartmentId(),
                'type' => CustomerContract::FACTORING,
                'sign_location' => $customerContractTransporter->getSignLocation(),
                'sign_date' => $customerContractTransporter->getSignDate(),
                'signed' => $customerContractTransporter->getSigned(),
            ]);

            $insurers = [];
            if ($customerContractTransporter->getAnnexes()) {
                foreach ($customerContractTransporter->getAnnexes() as $annex) {
                    $createdAnnex = CustomerContractAnnex::query()->create([
                        'customer_contract_id' => $contract->id,
                        'company_ids' => $annex['company_ids'],
                        'start_date' => $annex['start_date'],
                        'legal_person_monthly_subscription_costs_amount' => $annex['legal_person_monthly_subscription_costs_amount'],
                        'overdue_period_days' => $annex['overdue_period_days'],
                        'audit_fee_amount' => $annex['audit_fee_amount'],
                        'legal_person_monthly_vba_costs_amount' => $annex['legal_person_monthly_vba_costs_amount'],
                        'credit_insurance_coverage_percentage' => $annex['credit_insurance_coverage_percentage'],
                        'own_risk_amount' => $annex['own_risk_amount'],
                        'factoring_fee_domestic_turnover_percentage' => $annex['factoring_fee_domestic_turnover_percentage'],
                        'factoring_fee_abroad_turnover_percentage' => $annex['factoring_fee_abroad_turnover_percentage'],
                        'minimum_factoring_fee_amount' => $annex['minimum_factoring_fee_amount'],
                        'initial_term_months' => $annex['initial_term_months'],
                        'd_basics_setup_fee_amount' => $annex['d_basics_setup_fee_amount'],
                        'd_basics_yearly_subscription_amount' => $annex['d_basics_yearly_subscription_amount'],
                        'research_cost_amount' => $annex['research_cost_amount'],
                        'domestic_credit_limit_credit_check_to_amount' => $annex['domestic_credit_limit_credit_check_to_amount'],
                        'domestic_credit_limit_credit_check_amount' => $annex['domestic_credit_limit_credit_check_amount'],
                        'domestic_credit_limit_first_from_amount' => $annex['domestic_credit_limit_first_from_amount'],
                        'domestic_credit_limit_first_to_amount' => $annex['domestic_credit_limit_first_to_amount'],
                        'domestic_credit_limit_first_is_amount' => $annex['domestic_credit_limit_first_is_amount'],
                        'domestic_credit_limit_second_from_amount' => $annex['domestic_credit_limit_second_from_amount'],
                        'domestic_credit_limit_second_is_amount' => $annex['domestic_credit_limit_second_is_amount'],
                        'abroad_credit_limit_region_b_country_ids' => $annex['abroad_credit_limit_region_b_country_ids'],
                        'abroad_credit_limit_region_b_countries_amount' => $annex['abroad_credit_limit_region_b_countries_amount'],
                        'abroad_credit_limit_region_c_country_ids' => $annex['abroad_credit_limit_region_c_country_ids'],
                        'abroad_credit_limit_region_c_countries_amount' => $annex['abroad_credit_limit_region_c_countries_amount'],
                        'abroad_credit_limit_region_d_countries_amount' => $annex['abroad_credit_limit_region_d_countries_amount'],
                        'credit_provision_percentage' => $annex['credit_provision_percentage'],
                        'maximum_concentration_ratio_percentage' => $annex['maximum_concentration_ratio_percentage'],
                        'maximum_exposure' => $annex['maximum_exposure'],
                        'notice_period_months' => $annex['notice_period_months'],
                        'exceeding_interest_percentage' => $annex['exceeding_interest_percentage'],
                        'receivable_reserve_percentage' => $annex['receivable_reserve_percentage'],
                        'interest_rate_monthly_euribor' => $annex['interest_rate_monthly_euribor'],
                        'interest_rate_percentage' => $annex['interest_rate_percentage'],
                        'setup_fee_amount' => $annex['setup_fee_amount'],
                        'default_payment_term_days' => $annex['default_payment_term_days'],
                        'payment_deadline_days' => $annex['payment_deadline_days'],
                        'maximum_financing_amount' => $annex['maximum_financing_amount'],
                        'receivables_maximum_amount' => $annex['receivables_maximum_amount'],
                        'dossier_outsource_compensation_amount' => $annex['dossier_outsource_compensation_amount'],
                        'compensation_costs_additional_act_amount' => $annex['compensation_costs_additional_act_amount'],
                        'bank_statement_fee_cost_amount' => $annex['bank_statement_fee_cost_amount'],
                        'renewal_costs_limit' => $annex['renewal_costs_limit'],
                        'factoring_agreement_extension_fee_amount' => $annex['factoring_agreement_extension_fee_amount'],
                        'extension_period_months' => $annex['extension_period_months'],
                        'advance_percentage' => $annex['advance_percentage'],
                    ]);

                    if (optional($annex)['certainty_acts'] && count($annex['certainty_acts'])) {
                        $insurers = array_merge(
                            $insurers,
                            (new StoreOrUpdateCertaintiesAction)
                                ->run($annex['certainty_acts'], $createdAnnex)['insurers']
                        );
                    }

                    if (optional($annex)['required_documents'] && count($annex['required_documents'])) {
                        (new StoreOrUpdateRequiredDocumentsAction)
                            ->run($annex['required_documents'], $createdAnnex);
                    }
                }

                $contract->load(['customerAnnexes']);

                foreach ($contract->customerAnnexes as $key => $annex) {
                    $contract->customerAnnexes[$key]->certainty_acts = new ListContractAnnexCertaintyTask(
                        $this->listCertaintiesAction
                    )->run(
                        $annex,
                        $contract->salesStatus->currency,
                        $contract->salesStatus->correspondence_language,
                        $contract->operating_company_id
                    );

                    $contract->customerAnnexes[$key]->required_documents = new ListContractAnnexRequiredDocumentTask(
                        $this->listRequiredDocumentsAction
                    )->run(
                        $annex,
                        $contract->salesStatus->company->id,
                        $contract->salesStatus->currency,
                        $contract->salesStatus->correspondence_language,
                        $contract->operating_company_id
                    );
                }
            }

            if ($customerContractTransporter->getCompanies() && count($customerContractTransporter->getCompanies())) {
                $client = new ImpactFactoringManagementClient;

                foreach ($customerContractTransporter->getCompanies() as $company) {
                    $this->attachCompany(
                        $client,
                        $contract,
                        $company,
                        $customerContractTransporter->getSalesStatusId()
                    );
                }

                $contract->load(['companies']);
            }

            $this->createLogEntry($customerContractTransporter);

            $contract->load('salesStatus.customerLog.customerStatusTitle');

            return CustomerContractResource::make($contract)->additional(
                [
                    'sales_status' => $contract->salesStatus,
                    'meta_data' => [
                        'insurers' => $insurers,
                    ],
                ]
            );
        });
    }

    /**
     * @throws ModelNotFoundException|ApplicationException
     */
    private function attachCompany(
        ImpactFactoringManagementClient $client,
        CustomerContract $contract,
        array $company,
        string $salesStatusId
    ): void {
        /** @var Company $fetchedCompany */
        $fetchedCompany = $this->companyRepository->fetchOne(
            $company['company_id'],
            $salesStatusId
        );

        if (!$fetchedCompany) {
            throw (new ModelNotFoundException)->setModel(
                Company::class,
                $company['company_id']
            );
        }

        if ($fetchedCompany->type &&
            !in_array($fetchedCompany->type, [Company::TYPE_PROSPECT, Company::TYPE_ORGANOGRAM])
        ) {
            throw new ApplicationException('Invalid company type');
        }

        $fetchedContractCompany = $contract->companies->first(
            function ($contractCompany) use ($company): bool {
                return $contractCompany->pivot->company_id === $company['company_id'];
            }
        );

        $customerNumberId = $this->reserveCustomerNumber($client, $contract, $fetchedContractCompany);
        $ibanIsReserved = false;

        if ($company['iban_id']) {
            $ibanIsReserved = $this->reserveIban($client, $contract, $company);
        }

        $contract->companies()->attach($company['company_id'], [
            'iban_id' => $ibanIsReserved ? $company['iban_id'] : null,
            'signer_ids' => json_encode($company['signer_ids']),
            'client_number_id' => $customerNumberId ?? null,
        ]
        );
    }

    private function reserveCustomerNumber(
        ImpactFactoringManagementClient $client,
        CustomerContract $contract,
        ?Company $fetchedCompany
    ): ?int {
        if ($contract->salesStatus->abort_status || $fetchedCompany?->pivot->client_number_id) {
            return null;
        }

        if (app()->runningUnitTests() || app()->isLocal() || config('app.env') === 'staging') {
            return random_int(1, 9999);
        }

        $customerNumber = $client->reserveCustomerNumbers(1)[0];

        return $customerNumber->getId();
    }

    private function reserveIban(
        ImpactFactoringManagementClient $client,
        CustomerContract $contract,
        array $company
    ): bool {
        if ($contract->salesStatus->abort_status) {
            return false;
        }

        return $client->reserveBankAccount($company['iban_id']);
    }

    private function createLogEntry(CustomerContractTransporter $customerContractTransporter): void
    {
        $title = __('translations.form.contract');

        $log = [
            'sales_status_id' => $customerContractTransporter->getSalesStatusId(),
            'title' => ucfirst(
                __(
                    'translations.logbook.has_been_created',
                    ['title' => $title]
                )
            ),
            'action' => 'EVENT',
        ];

        event(new CustomerFormSavedEvent($log));
    }
}
