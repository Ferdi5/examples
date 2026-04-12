<?php

declare(strict_types=1);

namespace App\Domains\CustomerContract\Tasks;

use App\Domains\CustomerContract\Models\CustomerContractAnnex;

final class ListContractAnnexRequiredDocumentTask extends Action
{
    public function __construct(
        private readonly ListRequiredDocumentsAction $listRequiredDocumentsAction
    ) {
    }

    public function run(
        CustomerContractAnnex $annex,
        string|int $prospectCompanyId,
        string $currency,
        string $correspondenceLanguage,
        ?int $operatingCompanyId,
    ): array {
        $requiredDocuments = CustomerRequiredDocuments::query()
            ->with([
                'contractRequiredDocuments' => function (HasMany $query) use ($annex): void {
                    $query->where('customer_contract_annex_id', $annex->id);
                },
            ])
            ->where('language', $correspondenceLanguage)
            ->orderBy('sort_order')
            ->get();

        foreach ($requiredDocuments as $key => $requiredDocument) {
            $requiredDocument->required_documents = $requiredDocument->contractRequiredDocuments;
            unset($requiredDocument->contractRequiredDocuments);
        }

        return $this->listRequiredDocumentsAction->execute(
            $requiredDocuments,
            $prospectCompanyId,
            $currency,
            $operatingCompanyId
        );
    }
}
