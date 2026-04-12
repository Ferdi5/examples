<?php

declare(strict_types=1);

namespace App\Domains\Imports\DeedOfTransfer\Actions;

readonly class ImportDeedOfTransferAction implements WithMultipleSheets
{
    public function __construct(
        private int $administrationId,
        private string $customerNumber,
        private string $fileName
    ) {
    }

    public function sheets(): array
    {
        return [
            0 => new NAWImportAction($this->administrationId, $this->customerNumber, $this->fileName),
            1 => new InvoiceImportAction($this->administrationId, $this->customerNumber, $this->fileName),
        ];
    }
}
