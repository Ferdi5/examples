<?php

declare(strict_types=1);

namespace App\Domains\CustomerContract\Requests;

use Closure;

class StoreCustomerContractRequest extends FormRequest implements ValidatorAwareRule
{
    protected $validator;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(array $rules = []): array
    {
        $rules = array_merge([
            'sales_status_id' => 'required|numeric|exists:sales_status,id|exists:companies,sales_status_id',
            'contract.operating_company_id' => 'nullable|integer',
            'contract.funder_id' => 'nullable|integer',
            'contract.compartment_id' => 'nullable|integer',
            'contract.annexes' => [
                'nullable',
                'array',
                function (string $attribute, ?array $value, Closure $fail): ?Closure {
                    $annexes = collect($value);
                    $companyIds = $annexes->pluck('company_ids')->collapse();

                    if ($companyIds->unique()->count() !== $companyIds->count()) {
                        $fail('Not allowed to store with duplicate company id');
                    }

                    return null;
                },
            ],
            'contract.annexes.*.company_ids' => 'nullable|array',
            'contract.annexes.*.company_ids.*' => [
                'bail',
                'required',
                'integer',
                function (string $attribute, ?string $value, Closure $fail): ?Closure {
                    /** @var Company $company */
                    $company = Company::query()
                        ->where('id', $value)
                        ->where(
                            function (Builder $query): void {
                                $query
                                    ->whereHas(
                                        'salesStatus',
                                        function (Builder $query): void {
                                            $query->where('id', $this->input('sales_status_id'));
                                        }
                                    )
                                    ->whereNull('type')
                                    ->orWhereIn('type', [Company::TYPE_PROSPECT, Company::TYPE_ORGANOGRAM]);
                            },
                        )
                        ->first();

                    if (!$company?->id) {
                        $fail('Unable to find company');
                    }

                    return null;
                },
            ],
            'contract.sign_location' => 'nullable|string',
            'contract.sign_date' => 'bail|nullable|date_format:Y-m-d|after_or_equal:today',
            'contract.signed' => 'nullable|boolean',
            'contract.annexes.*.legal_person_monthly_subscription_costs_amount' => 'nullable|integer',
            'contract.annexes.*.overdue_period_days' => 'nullable|integer',
            'contract.annexes.*.audit_fee_amount' => 'nullable|integer',
            'contract.annexes.*.legal_person_monthly_vba_costs_amount' => 'nullable|integer',
            'contract.annexes.*.credit_insurance_coverage_percentage' => 'nullable|numeric|between:0,100|decimal:5',
            'contract.annexes.*.own_risk_amount' => 'nullable|integer',
            'contract.annexes.*.factoring_fee_domestic_turnover_percentage' => 'nullable|numeric|between:0,100|decimal:5',
            'contract.annexes.*.factoring_fee_abroad_turnover_percentage' => 'nullable|numeric|between:0,100|decimal:5',
            'contract.annexes.*.minimum_factoring_fee_amount' => 'nullable|integer',
            'contract.annexes.*.initial_term_months' => 'nullable|integer',
            'contract.annexes.*.d_basics_setup_fee_amount' => 'nullable|integer',
            'contract.annexes.*.d_basics_yearly_subscription_amount' => 'nullable|integer',
            'contract.annexes.*.research_cost_amount' => 'nullable|integer',
            'contract.annexes.*.domestic_credit_limit_credit_check_to_amount' => 'nullable|integer',
            'contract.annexes.*.domestic_credit_limit_credit_check_amount' => 'nullable|integer',
            'contract.annexes.*.domestic_credit_limit_first_from_amount' => 'nullable|integer',
            'contract.annexes.*.domestic_credit_limit_first_to_amount' => 'nullable|integer',
            'contract.annexes.*.domestic_credit_limit_first_is_amount' => 'nullable|integer',
            'contract.annexes.*.domestic_credit_limit_second_from_amount' => 'nullable|integer',
            'contract.annexes.*.domestic_credit_limit_second_is_amount' => 'nullable|integer',
            'contract.annexes.*.abroad_credit_limit_region_b_country_ids' => 'nullable|array',
            'contract.annexes.*.abroad_credit_limit_region_b_country_ids.*' => 'nullable|integer|exists:countries,id',
            'contract.annexes.*.abroad_credit_limit_region_b_countries_amount' => 'nullable|integer',
            'contract.annexes.*.abroad_credit_limit_region_c_country_ids' => 'nullable|array',
            'contract.annexes.*.abroad_credit_limit_region_c_country_ids.*' => 'nullable|integer|exists:countries,id',
            'contract.annexes.*.abroad_credit_limit_region_c_countries_amount' => 'nullable|integer',
            'contract.annexes.*.abroad_credit_limit_region_d_countries_amount' => 'nullable|integer',
            'contract.annexes.*.credit_provision_percentage' => 'nullable|numeric|between:0,100|decimal:5',
            'contract.annexes.*.maximum_concentration_ratio_percentage' => 'nullable|numeric|between:0,100|decimal:5',
            'contract.annexes.*.maximum_exposure' => 'nullable|string',
            'contract.annexes.*.notice_period_months' => 'nullable|integer',
            'contract.annexes.*.exceeding_interest_percentage' => 'nullable|numeric|between:0,100|decimal:5',
            'contract.annexes.*.receivable_reserve_percentage' => 'nullable|numeric|between:0,100|decimal:5',
            'contract.annexes.*.interest_rate_monthly_euribor' => 'nullable|integer',
            'contract.annexes.*.interest_rate_percentage' => 'nullable|numeric|between:0,100|decimal:5',
            'contract.annexes.*.setup_fee_amount' => 'nullable|integer',
            'contract.annexes.*.default_payment_term_days' => 'nullable|integer',
            'contract.annexes.*.payment_deadline_days' => 'nullable|integer',
            'contract.annexes.*.maximum_financing_amount' => 'nullable|integer',
            'contract.annexes.*.receivables_maximum_amount' => 'nullable|integer',
            'contract.annexes.*.dossier_outsource_compensation_amount' => 'nullable|integer',
            'contract.annexes.*.compensation_costs_additional_act_amount' => 'nullable|integer',
            'contract.annexes.*.bank_statement_fee_cost_amount' => 'nullable|integer',
            'contract.annexes.*.renewal_costs_limit' => 'nullable|string',
            'contract.annexes.*.factoring_agreement_extension_fee_amount' => 'nullable|integer',
            'contract.annexes.*.extension_period_months' => 'nullable|integer',
            'contract.annexes.*.advance_percentage' => 'nullable|numeric|between:0,100|decimal:5',
            'contract.annexes.*.certainty_acts' => 'nullable|array',
            'contract.annexes.*.required_documents' => 'nullable|array',
            'contract.companies.*.company_id' => [
                'bail',
                'required',
                'integer',
                function (string $attribute, ?string $value, Closure $fail): ?Closure {
                    /** @var Company $company */
                    $company = Company::query()
                        ->where('id', $value)
                        ->where(
                            function (Builder $query): void {
                                $query
                                    ->whereHas(
                                        'salesStatus',
                                        function (Builder $query): void {
                                            $query->where('id', $this->input('sales_status_id'));
                                        }
                                    )
                                    ->whereNull('type')
                                    ->orWhereIn('type', [Company::TYPE_PROSPECT, Company::TYPE_ORGANOGRAM]);
                            },
                        )
                        ->first();

                    if (!$company?->id) {
                        $fail('Unable to find company');
                    }

                    return null;
                },
            ],
            'contract.companies.*.iban_id' => 'nullable|integer',
            'contract.companies.*.signer_ids.*' => 'nullable|integer|exists:persons,id|exists:company_person_contacts,person_id|exists:company_person_competence,person_id',
            'contract.companies.*.signer_ids' => 'bail|nullable|array',
            'contract.companies.*.client_number_id' => 'nullable|integer',
        ], $rules);

        if (!count($this->input('contract.annexes') ?? [])) {
            return $rules;
        }

        if (!$this->input('contract.id')) {
            $rules = $this->getStartDateRules($rules);
        }

        return $this->getCertaintyAndRequiredDocumentRules($rules);
    }

    private function getCertaintyAndRequiredDocumentRules(array $rules): array
    {
        $certaintyActRequest = (new CertaintyActRequest);
        $requiredDocumentsRequest = (new RequiredDocumentsRequest);

        foreach ($certaintyActRequest->rules() as $key => $rule) {
            $rules['contract.annexes.*.' . $key] = $rule;
        }

        foreach ($requiredDocumentsRequest->rules() as $key => $rule) {
            $rules['contract.annexes.*.' . $key] = $rule;
        }

        return array_merge(
            $rules,
            $this->getSelectedCertaintyAndRequiredDocumentRules($certaintyActRequest, $requiredDocumentsRequest)
        );
    }

    private function getSelectedCertaintyAndRequiredDocumentRules(
        CertaintyActRequest $certaintyActRequest,
        RequiredDocumentsRequest $requiredDocumentsRequest
    ): array {
        $rules = [];

        foreach ($this->input('contract.annexes') as $annexKey => $annex) {
            if (optional($annex)['certainty_acts'] && count($annex['certainty_acts'])) {
                $certaintyRules = $certaintyActRequest->getSelectedCertaintyRules($annex['certainty_acts']);

                foreach ($certaintyRules as $key => $rule) {
                    $rules["contract.annexes.$annexKey.$key"] = $rule;
                }
            }

            if (optional($annex)['required_documents'] && count($annex['required_documents'])) {
                $requiredDocumentRules = $requiredDocumentsRequest->getSelectedRequiredDocumentRules(
                    $annex['required_documents']
                );

                foreach ($requiredDocumentRules as $key => $rule) {
                    $rules["contract.annexes.$annexKey.$key"] = $rule;
                }
            }
        }

        return $rules;
    }

    private function getStartDateRules(array $rules): array
    {
        $signDate = $this->input('contract.sign_date');

        foreach ($this->input('contract.annexes') as $key => $annex) {
            $validationRule = 'bail|nullable|date_format:Y-m-d|after_or_equal:today';

            if ($signDate) {
                $validationRule = $validationRule . '|after_or_equal:' . $signDate;
            }

            $rules['contract.annexes.' . $key . '.start_date'] = $validationRule;
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!count($this->input('contract.companies') ?? [])) {
                    return null;
                }

                foreach ($this->input('contract.companies') as $key => $contractCompany) {
                    $signerIds = collect($contractCompany['signer_ids']);

                    if (!$signerIds->count()) {
                        continue;
                    }

                    $persons = Person::query()
                        ->whereIn('id', $signerIds)
                        ->whereHas('companyPersonContact', function (Builder $query) use ($contractCompany): void {
                            $query->where('company_id', $contractCompany['company_id']);
                        })
                        ->withWhereHas(
                            'companyCompetence',
                            function (Builder|BelongsToMany $query) use ($contractCompany): void {
                                $query->where('company_id', $contractCompany['company_id']);
                                $query->whereIn(
                                    'competence',
                                    [
                                        Str::lower(CompanyPersonCompetence::INDEPENDENT_SIGN),
                                        Str::lower(CompanyPersonCompetence::CO_SIGN),
                                    ]
                                );
                            }
                        )
                        ->get();

                    if (!$persons->count()) {
                        $validator->errors()->add('contract.companies.' . $key . '.signer_ids', 'No signers found');
                    }

                    $coSignPersons = collect($persons)->filter(
                        function (Person $person) {
                            return $person->companyCompetence[0]->pivot->competence === Str::lower(
                                CompanyPersonCompetence::CO_SIGN
                            );
                        }
                    );

                    if ($persons->count() === $coSignPersons->count() && $coSignPersons->count() === 1) {
                        $validator->errors()->add(
                            'contract.companies.' . $key . '.signer_ids',
                            'At least 2 co signers are required'
                        );
                    }
                }
            },
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'sales_status_id' => $this->route('salesStatusId'),
        ]);
    }
}
