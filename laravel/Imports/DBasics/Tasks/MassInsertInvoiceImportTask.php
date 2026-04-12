<?php

declare(strict_types=1);

namespace App\Domains\Imports\DBasics\Tasks;

use Throwable;

class MassInsertInvoiceImportTask
{
    /**
     * @param array<string|float|Carbon|null> $debtors
     * @param array<string|int|null>          $invoices
     * @param array<string|null>              $invoiceNotes
     *
     * @throws Throwable
     */
    public function render(array $debtors, array $invoices, array $invoiceNotes): void
    {
        $existingDebtorCodes = $this->getExistingDebtors($debtors);
        $existingInvoiceCodes = $this->getExistingInvoices($invoices);

        DB::transaction(
            function () use (
                $debtors,
                $invoices,
                $invoiceNotes,
                $existingDebtorCodes,
                $existingInvoiceCodes
            ): void {
                $newDebtors = collect($debtors)
                    ->whereNotIn('debtor_code', $existingDebtorCodes)
                    ->toArray();

                Debtor::insert($newDebtors);

                $invoices = collect($invoices)
                    ->whereNotIn('invoice_code', $existingInvoiceCodes)
                    ->toArray();
                $invoices = $this->setInvoiceDebtorId($invoices, $debtors);

                Invoice::insert($invoices);

                $invoiceNotes = collect($invoiceNotes)
                    ->whereNotIn('invoice_code', $existingInvoiceCodes)
                    ->toArray();
                $invoiceNotes = $this->setInvoiceNoteInvoiceId($invoices, $invoiceNotes);

                InvoiceNote::insert($invoiceNotes);
            }
        );
    }

    /**
     * @param array<string|int|null> $invoices
     */
    private function getExistingInvoices(array $invoices): array
    {
        $invoiceCodes = collect($invoices)
            ->pluck('invoice_code')
            ->unique()
            ->values();

        return Invoice::whereIn('invoice_code', $invoiceCodes)
            ->pluck('invoice_code')
            ->toArray();
    }

    /**
     * @param array<string|float|Carbon|null> $debtors
     */
    private function getExistingDebtors(array $debtors): array
    {
        $debtorCodes = collect($debtors)
            ->pluck('debtor_code')
            ->unique()
            ->values();

        return Debtor::whereIn('debtor_code', $debtorCodes)
            ->pluck('debtor_code')
            ->all();
    }

    /**
     * @param array<string|int|null>          $invoices
     * @param array<string|float|Carbon|null> $debtors
     */
    private function setInvoiceDebtorId(array $invoices, array $debtors): array
    {
        $debtorCodes = collect($debtors)->pluck('debtor_code');
        $debtorCodes = Debtor::whereIn('debtor_code', $debtorCodes)
            ->pluck('id', 'debtor_code');

        foreach ($invoices as $key => $invoice) {
            $invoices[$key]['debtor_id'] = $debtorCodes[$invoice['debtor_code']];
            unset($invoices[$key]['debtor_code']);
        }

        return $invoices;
    }

    /**
     * @param array<string|int|null> $invoices
     * @param array<string|null>     $invoiceNotes
     */
    private function setInvoiceNoteInvoiceId(array $invoices, array $invoiceNotes): array
    {
        $invoiceCodes = collect($invoices)->pluck('invoice_code');
        $invoiceCodes = Invoice::whereIn('invoice_code', $invoiceCodes)
            ->pluck('id', 'invoice_code');

        foreach ($invoiceNotes as $key => $invoiceNote) {
            $invoiceNotes[$key]['invoice_id'] = $invoiceCodes[$invoiceNote['invoice_code']];
            unset($invoiceNotes[$key]['invoice_code']);
        }

        return $invoiceNotes;
    }
}
