<?php

declare(strict_types=1);

namespace App\Domains\CustomerContract\Models;

/**
 * @property int                  $id
 * @property int                  $customer_contract_id,
 * @property string|null          $company_ids,
 * @property string|null          $start_date,
 * @property int|null             $legal_person_monthly_subscription_costs_amount,
 * @property int|null             $overdue_period_days,
 * @property int|null             $audit_fee_amount,
 * @property int|null             $legal_person_monthly_vba_costs_amount,
 * @property int|null             $credit_insurance_coverage_percentage,
 * @property int|null             $own_risk_amount,
 * @property int|null             $factoring_fee_domestic_turnover_percentage,
 * @property int|null             $factoring_fee_abroad_turnover_percentage,
 * @property int|null             $minimum_factoring_fee_amount,
 * @property int|null             $initial_term_months,
 * @property int|null             $d_basics_setup_fee_amount,
 * @property int|null             $d_basics_yearly_subscription_amount,
 * @property int|null             $research_cost_amount,
 * @property int|null             $domestic_credit_limit_credit_check_to_amount,
 * @property int|null             $domestic_credit_limit_credit_check_amount,
 * @property int|null             $domestic_credit_limit_first_from_amount,
 * @property int|null             $domestic_credit_limit_first_to_amount,
 * @property int|null             $domestic_credit_limit_first_is_amount,
 * @property int|null             $domestic_credit_limit_second_from_amount,
 * @property int|null             $domestic_credit_limit_second_is_amount,
 * @property string|null          $abroad_credit_limit_region_b_country_ids,
 * @property int|null             $abroad_credit_limit_region_b_countries_amount,
 * @property string|null          $abroad_credit_limit_region_c_country_ids,
 * @property int|null             $abroad_credit_limit_region_c_countries_amount,
 * @property int|null             $abroad_credit_limit_region_d_countries_amount,
 * @property int|null             $credit_provision_percentage,
 * @property int|null             $maximum_concentration_ratio_percentage,
 * @property string|null          $maximum_exposure,
 * @property int|null             $notice_period_months,
 * @property int|null             $exceeding_interest_percentage,
 * @property int|null             $receivable_reserve_percentage,
 * @property int|null             $interest_rate_monthly_euribor,
 * @property int|null             $interest_rate_percentage,
 * @property int|null             $setup_fee_amount,
 * @property int|null             $default_payment_term_days,
 * @property int|null             $payment_deadline_days,
 * @property int|null             $maximum_financing_amount,
 * @property int|null             $receivables_maximum_amount,
 * @property int|null             $dossier_outsource_compensation_amount,
 * @property int|null             $compensation_costs_additional_act_amount,
 * @property int|null             $bank_statement_fee_cost_amount,
 * @property string|null          $renewal_costs_limit,
 * @property int|null             $factoring_agreement_extension_fee_amount,
 * @property int|null             $extension_period_months,
 * @property int|null             $advance_percentage
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
final class CustomerContractAnnex extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'customer_contract_annex';
    /** @var string[] */
    protected $fillable = [
        'customer_contract_id',
        'company_ids',
        'start_date',
        'legal_person_monthly_subscription_costs_amount',
        'overdue_period_days',
        'audit_fee_amount',
        'legal_person_monthly_vba_costs_amount',
        'credit_insurance_coverage_percentage',
        'own_risk_amount',
        'factoring_fee_domestic_turnover_percentage',
        'factoring_fee_abroad_turnover_percentage',
        'minimum_factoring_fee_amount',
        'initial_term_months',
        'd_basics_setup_fee_amount',
        'd_basics_yearly_subscription_amount',
        'research_cost_amount',
        'domestic_credit_limit_credit_check_to_amount',
        'domestic_credit_limit_credit_check_amount',
        'domestic_credit_limit_first_from_amount',
        'domestic_credit_limit_first_to_amount',
        'domestic_credit_limit_first_is_amount',
        'domestic_credit_limit_second_from_amount',
        'domestic_credit_limit_second_is_amount',
        'abroad_credit_limit_region_b_country_ids',
        'abroad_credit_limit_region_b_countries_amount',
        'abroad_credit_limit_region_c_country_ids',
        'abroad_credit_limit_region_c_countries_amount',
        'abroad_credit_limit_region_d_countries_amount',
        'credit_provision_percentage',
        'maximum_concentration_ratio_percentage',
        'maximum_exposure',
        'notice_period_months',
        'exceeding_interest_percentage',
        'receivable_reserve_percentage',
        'interest_rate_monthly_euribor',
        'interest_rate_percentage',
        'setup_fee_amount',
        'default_payment_term_days',
        'payment_deadline_days',
        'maximum_financing_amount',
        'receivables_maximum_amount',
        'dossier_outsource_compensation_amount',
        'compensation_costs_additional_act_amount',
        'bank_statement_fee_cost_amount',
        'renewal_costs_limit',
        'factoring_agreement_extension_fee_amount',
        'extension_period_months',
        'advance_percentage',
    ];
    protected $casts = [
        'company_ids' => 'array',
        'abroad_credit_limit_region_b_country_ids' => 'array',
        'abroad_credit_limit_region_c_country_ids' => 'array',
    ];

    public function customerContract(): BelongsTo
    {
        return $this->belongsTo(CustomerContract::class);
    }

    public function certaintyActs(): HasMany
    {
        return $this->hasMany(CustomerContractAnnexCertaintiesRel::class, 'customer_contract_annex_id');
    }

    public function requiredDocuments(): HasMany
    {
        return $this->hasMany(CustomerContractAnnexRequiredDocumentsRel::class, 'customer_contract_annex_id');
    }

    public function certaintiesRel(): BelongsToMany
    {
        return $this->belongsToMany(
            CustomerCertainties::class,
            'customer_contract_annex_certainties_rel',
            'customer_contract_annex_id',
            'customer_certainties_id'
        )->withPivot(
            'id',
            'selected',
            'values'
        );
    }

    public function requiredDocumentsRel(): BelongsToMany
    {
        return $this->belongsToMany(
            CustomerRequiredDocuments::class,
            'customer_contract_annex_required_documents_rel',
            'customer_contract_annex_id',
            'customer_required_documents_id'
        )->withPivot(
            'id',
            'selected',
            'values'
        );
    }
}
