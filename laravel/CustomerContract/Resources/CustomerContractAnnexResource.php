<?php

declare(strict_types=1);

namespace App\Domains\CustomerContract\Resources;

use App\Domains\CustomerContract\Models\CustomerContractAnnex;

/** @property CustomerContractAnnex $resource */
final class CustomerContractAnnexResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);

        Assert::isInstanceOf($resource, CustomerContractAnnex::class);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'company_ids' => $this->resource->company_ids,
            'start_date' => $this->resource->start_date,
            'legal_person_monthly_subscription_costs_amount' => $this->resource->legal_person_monthly_subscription_costs_amount,
            'overdue_period_days' => $this->resource->overdue_period_days,
            'audit_fee_amount' => $this->resource->audit_fee_amount,
            'legal_person_monthly_vba_costs_amount' => $this->resource->legal_person_monthly_vba_costs_amount,
            'credit_insurance_coverage_percentage' => $this->resource->credit_insurance_coverage_percentage,
            'own_risk_amount' => $this->resource->own_risk_amount,
            'factoring_fee_domestic_turnover_percentage' => $this->resource->factoring_fee_domestic_turnover_percentage,
            'factoring_fee_abroad_turnover_percentage' => $this->resource->factoring_fee_abroad_turnover_percentage,
            'minimum_factoring_fee_amount' => $this->resource->minimum_factoring_fee_amount,
            'initial_term_months' => $this->resource->initial_term_months,
            'd_basics_setup_fee_amount' => $this->resource->d_basics_setup_fee_amount,
            'd_basics_yearly_subscription_amount' => $this->resource->d_basics_yearly_subscription_amount,
            'research_cost_amount' => $this->resource->research_cost_amount,
            'domestic_credit_limit_credit_check_to_amount' => $this->resource->domestic_credit_limit_credit_check_to_amount,
            'domestic_credit_limit_credit_check_amount' => $this->resource->domestic_credit_limit_credit_check_amount,
            'domestic_credit_limit_first_from_amount' => $this->resource->domestic_credit_limit_first_from_amount,
            'domestic_credit_limit_first_to_amount' => $this->resource->domestic_credit_limit_first_to_amount,
            'domestic_credit_limit_first_is_amount' => $this->resource->domestic_credit_limit_first_is_amount,
            'domestic_credit_limit_second_from_amount' => $this->resource->domestic_credit_limit_second_from_amount,
            'domestic_credit_limit_second_is_amount' => $this->resource->domestic_credit_limit_second_is_amount,
            'abroad_credit_limit_region_b_country_ids' => $this->resource->abroad_credit_limit_region_b_country_ids,
            'abroad_credit_limit_region_b_countries_amount' => $this->resource->abroad_credit_limit_region_b_countries_amount,
            'abroad_credit_limit_region_c_country_ids' => $this->resource->abroad_credit_limit_region_c_country_ids,
            'abroad_credit_limit_region_c_countries_amount' => $this->resource->abroad_credit_limit_region_c_countries_amount,
            'abroad_credit_limit_region_d_countries_amount' => $this->resource->abroad_credit_limit_region_d_countries_amount,
            'credit_provision_percentage' => $this->resource->credit_provision_percentage,
            'maximum_concentration_ratio_percentage' => $this->resource->maximum_concentration_ratio_percentage,
            'maximum_exposure' => $this->resource->maximum_exposure,
            'notice_period_months' => $this->resource->notice_period_months,
            'exceeding_interest_percentage' => $this->resource->exceeding_interest_percentage,
            'receivable_reserve_percentage' => $this->resource->receivable_reserve_percentage,
            'interest_rate_monthly_euribor' => $this->resource->interest_rate_monthly_euribor,
            'interest_rate_percentage' => $this->resource->interest_rate_percentage,
            'setup_fee_amount' => $this->resource->setup_fee_amount,
            'default_payment_term_days' => $this->resource->default_payment_term_days,
            'payment_deadline_days' => $this->resource->payment_deadline_days,
            'maximum_financing_amount' => $this->resource->maximum_financing_amount,
            'receivables_maximum_amount' => $this->resource->receivables_maximum_amount,
            'dossier_outsource_compensation_amount' => $this->resource->dossier_outsource_compensation_amount,
            'compensation_costs_additional_act_amount' => $this->resource->compensation_costs_additional_act_amount,
            'bank_statement_fee_cost_amount' => $this->resource->bank_statement_fee_cost_amount,
            'renewal_costs_limit' => $this->resource->renewal_costs_limit,
            'factoring_agreement_extension_fee_amount' => $this->resource->factoring_agreement_extension_fee_amount,
            'extension_period_months' => $this->resource->extension_period_months,
            'advance_percentage' => $this->resource->advance_percentage,
            'certainty_acts' => $this->resource->certainty_acts ?? [],
            'required_documents' => $this->resource->required_documents ?? [],
        ];
    }
}
