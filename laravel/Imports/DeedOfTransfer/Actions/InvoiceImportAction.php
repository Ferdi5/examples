<?php

declare(strict_types=1);

namespace App\Domains\Imports\DeedOfTransfer\Actions;

use App\Domains\Imports\DBasics\Tasks\MassInsertInvoiceImportTask;
use Throwable;

class InvoiceImportAction implements OnEachRow, WithHeadingRow
{
    private int $chunk = 1000;
    private array $debtors = [];
    private array $invoices = [];
    private array $invoiceNotes = [];

    public function __construct(
        private readonly int $administrationId,
        private readonly string $customerNumber,
        private readonly string $fileName
    ) {
    }

    public function headingRow(): int
    {
        return 2;
    }

    /**
     * @throws Throwable
     */
    public function onRow(Row $row): void
    {
        if (!$this->administrationId) {
            Log::error('Invalid row detected, no administration id found', [
                'file_name' => $this->fileName,
                'row_number' => $row->getIndex(),
                'customer_number' => $this->customerNumber,
            ]);

            return;
        }

        if ($this->customerNumber !== optional($row)['client_id']) {
            Log::error('Invalid row detected, incorrect client id', [
                'file_name' => $this->fileName,
                'row_number' => $row->getIndex(),
                'customer_number' => $this->customerNumber,
            ]);

            return;
        }

        if (!optional($row)['factuurnummer']) {
            Log::error('Invalid row detected, No invoice number found', [
                'file_name' => $this->fileName,
                'row_number' => $row->getIndex(),
                'customer_number' => $this->customerNumber,
            ]);

            return;
        }

        if (!optional($row)['debiteurnummer']) {
            Log::error('Invalid row detected, no debtor number found', [
                'file_name' => $this->fileName,
                'row_number' => $row->getIndex(),
                'customer_number' => $this->customerNumber,
            ]);

            return;
        }

        $this->debtors[] = [
            'administration_id' => $this->administrationId,
            'debtor_code' => $row['debiteurnummer'],
        ];

        $this->invoices[] = [
            'debtor_code' => $row['debiteurnummer'],
            'invoice_code' => $row['factuurnummer'],
            'invoice_date' => $this->formatDate($row['factuurdatum']),
            'date_received' => Carbon::now(),
            'due_date' => $this->formatDate($row['vervaldatum']),
            'amount' => $this->validateAmount($row['bedrag']),
            'outstanding_amount' => $this->validateAmount($row['openstaand_bedrag']),
            'currency' => $row['valuta'],
            'exchange_rate' => $this->validateAmount($row['koers']),
        ];

        if ($row['omschrijving']) {
            $this->invoiceNotes[] = [
                'invoice_code' => $row['factuurnummer'],
                'content' => $row['omschrijving'],
            ];
        }

        if (count($this->invoices) >= $this->chunk) {
            $this->insertDataInDatabase();
        }
    }

    /**
     * @throws Throwable
     */
    public function __destruct()
    {
        $this->insertDataInDatabase();
    }

    /**
     * @throws Throwable
     */
    private function insertDataInDatabase(): void
    {
        if (!count($this->invoices)) {
            return;
        }

        new MassInsertInvoiceImportTask()->render($this->debtors, $this->invoices, $this->invoiceNotes);

        $this->debtors = [];
        $this->invoices = [];
        $this->invoiceNotes = [];
    }

    public function formatDate(string $value): ?Carbon
    {
        $value = trim($value);

        $formats = [
            'j-n-y', // 5-6-25
            'j-n-Y', // 5-6-2025
            'j-m-y', // 5-06-25
            'j-m-Y', // 5-06-2025
            'd-n-y', // 05-6-25
            'd-n-Y', // 05-6-2025
            'd-m-y', // 05-06-25
            'd-m-Y', // 05-06-2025
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (Throwable) {
                // try next format
            }
        }

        return null;
    }

    private function validateAmount(string $value): ?string
    {
        if (gettype($value) !== 'string' || !trim($value)) {
            return null;
        }

        $value = Str::replace(',', '.', trim($value));

        if (!is_numeric($value)) {
            return null;
        }

        return $value;
    }
}
