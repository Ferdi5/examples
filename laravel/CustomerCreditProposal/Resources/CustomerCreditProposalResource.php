<?php

declare(strict_types=1);

namespace App\Domains\CustomerCreditProposal\Resources;

/** @property CustomerCreditProposal $resource */
final class CustomerCreditProposalResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'sales_status_id' => $this->resource->sales_status_id,
            'person_id' => $this->resource->person_id,
            'person' => $this->whenLoaded('person'),
            'status' => $this->resource->status,
            'limit_costs' => $this->resource->limit_costs,
            'limit_costs_description' => $this->resource->limit_costs_description,
            'deviating_provisions' => is_string($this->resource->deviating_provisions) ?
                json_decode($this->resource->deviating_provisions)
                : $this->resource->deviating_provisions,
            'seasonal_influences' => $this->resource->seasonal_influences,
            'seasonal_influences_description' => $this->resource->seasonal_influences_description,
            'billing_interval' => $this->resource->billing_interval,
            'debtors_appear_as_creditors' => $this->resource->debtors_appear_as_creditors,
            'debtors_appear_as_creditors_description' => $this->resource->debtors_appear_as_creditors_description,
            'nature_of_billing' => $this->resource->nature_of_billing,
            'bonus_or_payment_discounts' => $this->resource->bonus_or_payment_discounts,
            'bonus_or_payment_discounts_description' => $this->resource->bonus_or_payment_discounts_description,
            'credit_insured_debtors' => $this->resource->credit_insured_debtors,
            'credit_insured_debtors_description' => $this->resource->credit_insured_debtors_description,
            'write_offs_debtors_past_three_years' => $this->resource->write_offs_debtors_past_three_years,
            'doubtful_debtors' => $this->resource->doubtful_debtors,
            'doubtful_debtors_description' => $this->resource->doubtful_debtors_description,
            'general_delivery_and_payment_conditions' => $this->resource->general_delivery_and_payment_conditions,
            'portfolio_concentrations' => $this->resource->portfolio_concentrations,
            'portfolio_concentrations_description' => $this->resource->portfolio_concentrations_description,
            'order_to_cash_process' => $this->resource->order_to_cash_process,
            'general_description' => $this->resource->general_description,
            'funding_request_reason' => $this->resource->funding_request_reason,
            'current_funder_description' => $this->resource->current_funder_description,
            'requested_funding_description' => $this->resource->requested_funding_description,
            'financials_description' => $this->resource->financials_description,
            'background_request_description' => $this->resource->background_request_description,
            'main_risks' => $this->resource->main_risks,
            'pricing_description' => $this->resource->pricing_description,
            'decision_description' => $this->resource->decision_description,
            'actions_to_be_taken' => $this->resource->actions_to_be_taken,
            'file_ids' => is_string($this->resource->file_ids) ?
                json_decode($this->resource->file_ids)
                : $this->resource->file_ids,
            'evaluations' => $this->whenLoaded('evaluations') ?
                CustomerCreditProposalEvaluationResourceCollection::make($this->whenLoaded('evaluations'))
                : $this->whenLoaded('evaluations'),
            'sales_status' => $this->whenLoaded('salesStatus'),
        ];
    }
}
