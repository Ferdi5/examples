<?php

declare(strict_types=1);

namespace App\Domains\CustomerContract\Requests;

use App\Domains\CustomerContract\Models\CustomerContract;
use Closure;

final class UpdateCustomerContractRequest extends StoreCustomerContractRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(array $rules = []): array
    {
        $rules = [
            'sales_status_id' => 'required|numeric|exists:customer_contract,sales_status_id|exists:sales_status,id|exists:companies,sales_status_id',
            'contract_id' => 'required|numeric|exists:customer_contract,id',
            'contract.annexes.*.id' => [
                'nullable',
                'integer',
                Rule::exists('customer_contract_annex', 'id')->where(
                    function (Builder $query): void {
                        $query->where('customer_contract_id', $this->input('contract_id'));
                    },
                ),
            ],
            'contract.annexes.*.certainty_acts.*.certainty_act_id' => 'sometimes|integer|exists:customer_contract_annex_certainties_rel,id',
            'contract.annexes.*.required_documents.*.required_document_rel_id' => 'sometimes|integer|exists:customer_contract_annex_required_documents_rel,id',
            'contract.sign_date' => [
                'bail',
                'nullable',
                'date_format:Y-m-d',
                function (string $attribute, ?string $value, Closure $fail): ?Closure {
                    if (!$value) {
                        return null;
                    }

                    $contract = CustomerContract::query()
                        ->where('id', $this->input('contract_id'))
                        ->where('sales_status_id', $this->input('sales_status_id'))
                        ->first();

                    $date = CarbonImmutable::createFromFormat('Y-m-d', $value);
                    $today = CarbonImmutable::now()->format('Y-m-d');

                    if ($value !== $contract?->sign_date && !$date->greaterThanOrEqualTo($today)) {
                        $fail('Not allowed to set sign date before today');
                    }

                    return null;
                },
            ],
        ];

        if (count($this->input('contract.annexes') ?? [])) {
            $rules = $this->getStartDateRules($rules);
        }

        return parent::rules($rules);
    }

    private function getStartDateRules(array $rules): array
    {
        $contract = CustomerContract::query()
            ->where('id', $this->input('contract_id'))
            ->where('sales_status_id', $this->input('sales_status_id'))
            ->with('customerAnnexes')
            ->first();

        $signDate = $this->input('contract.sign_date');

        foreach ($this->input('contract.annexes') as $key => $annex) {
            if (!optional($annex)['id'] || !optional($annex)['start_date']) {
                $validationRule = 'bail|nullable|date_format:Y-m-d|after_or_equal:today';

                if ($signDate) {
                    $validationRule = $validationRule . '|after_or_equal:' . $signDate;
                }

                $rules['contract.annexes.' . $key . '.start_date'] = $validationRule;

                continue;
            }

            $oldAnnex = $contract->customerAnnexes->find($annex['id']);

            if (optional($annex)['start_date'] !== $oldAnnex?->start_date) {
                $validationRule = 'bail|nullable|date_format:Y-m-d|after_or_equal:today';

                if ($signDate) {
                    $validationRule = $validationRule . '|after_or_equal:' . $signDate;
                }

                $rules['contract.annexes.' . $key . '.start_date'] = $validationRule;

                continue;
            }

            if ($signDate && $signDate !== $contract?->sign_date) {
                $rules['contract.annexes.' . $key . '.start_date'] = 'bail|nullable|date_format:Y-m-d|after_or_equal:' . $signDate;

                continue;
            }

            $rules['contract.annexes.' . $key . '.start_date'] = 'nullable|date_format:Y-m-d';
        }

        return $rules;
    }

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'sales_status_id' => $this->route('salesStatusId'),
            'contract_id' => $this->route('contract'),
        ]);
    }
}
