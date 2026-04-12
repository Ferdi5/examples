<?php

declare(strict_types=1);

namespace App\Domains\Imports\DeedOfTransfer\Actions;

use App\Domains\Imports\DBasics\Tasks\MassInsertNawImportTask;
use Throwable;

class NAWImportAction implements OnEachRow, WithHeadingRow
{
    private int $chunk = 1000;
    private array $debtors = [];
    private array $companies = [];
    private array $addresses = [];
    private array $persons = [];

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

        if ($this->customerNumber !== optional($row)['administratie']) {
            Log::error('Invalid row detected, incorrect administratie', [
                'file_name' => $this->fileName,
                'row_number' => $row->getIndex(),
                'customer_number' => $this->customerNumber,
            ]);

            return;
        }

        if (!optional($row)['debnr']) {
            Log::error('Invalid row detected, no debtor number found', [
                'file_name' => $this->fileName,
                'row_number' => $row->getIndex(),
                'customer_number' => $this->customerNumber,
            ]);

            return;
        }

        if (!optional($row)['debiteurnaam']) {
            Log::error('Invalid row detected, no debtor name found', [
                'file_name' => $this->fileName,
                'row_number' => $row->getIndex(),
                'customer_number' => $this->customerNumber,
            ]);

            return;
        }

        $this->companies[] = [
            'address_id' => $row['adres'],
            'name' => $row['debiteurnaam'],
            'business_registration_number' => $row['kvk'],
            'vat_number' => $row['btw_nummer'],
            'general_phone_number' => $row['telefoon'],
            'general_mobile_phone' => $row['mobiel'],
            'general_email' => $row['e_mail'],
        ];

        if ($row['adres']) {
            $this->addresses[] = [
                'address' => $row['adres'],
                'zip_code' => $row['postcode'],
                'city' => $row['plaats'],
                'country' => $row['landiso_code'],
            ];
        }

        $this->debtors[] = [
            'company_name' => $row['debiteurnaam'],
            'administration_id' => $this->administrationId,
            'debtor_code' => $row['debnr'],
        ];

        $this->persons[] = [
            'company_name' => $row['debiteurnaam'],
            'first_name' => $row['voornaam'],
            'last_name' => $row['achternaam'],
            'job_title' => $row['functie'],
        ];

        if (count($this->companies) >= $this->chunk) {
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
        if (!count($this->companies)) {
            return;
        }

        new MassInsertNawImportTask()->render(
            $this->companies,
            $this->addresses,
            $this->debtors,
            $this->persons,
        );

        $this->companies = [];
        $this->addresses = [];
        $this->debtors = [];
        $this->persons = [];
    }
}
