<?php

declare(strict_types=1);

namespace App\Domains\CustomerContract\Tasks;

use App\Domains\CustomerContract\Models\CustomerContractAnnex;

final class ListContractAnnexCertaintyTask extends Action
{
    public function __construct(
        private readonly ListCertaintiesAction $listCertaintiesAction
    ) {
    }

    public function run(
        CustomerContractAnnex $annex,
        string $currency,
        string $correspondenceLanguage,
        ?int $operatingCompanyId,
    ): array {
        $certainties = CustomerCertainties::query()
            ->with([
                'contractCertaintyActs' => function (HasMany $query) use ($annex): void {
                    $query->where('customer_contract_annex_id', $annex->id);
                },
            ])
            ->where('language', $correspondenceLanguage)
            ->orderBy('sort_order')
            ->get();

        foreach ($certainties as $certainty) {
            $certainty->certainty_acts = $certainty->contractCertaintyActs;
            unset($certainty->contractCertaintyActs);
        }

        return $this->listCertaintiesAction->execute(
            $certainties,
            $currency,
            $correspondenceLanguage,
            $operatingCompanyId
        );
    }
}
