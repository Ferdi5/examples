<?php

declare(strict_types=1);

namespace tests\Imports;

use Tests\TestCase;

final class ImportDeedOfTransferTest extends TestCase
{
    public function test_creating_unauthenticated(): void
    {
        $this->postJson('/api/import/deed-of-transfer')->assertUnauthorized();
    }

    public function test_creating_with_correct_data(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                '2344',
                'Nice debtor',
                'Straatweg 84',
                '3451 GT',
                'Amsterdam',
                'NL',
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        $this->assertDatabaseHas(
            'addresses',
            [
                'address' => 'Straatweg 84',
                'zip_code' => '3451 GT',
                'city' => 'Amsterdam',
                'country' => 'NL',
            ]
        );
        $this->assertDatabaseHas(
            'companies',
            [
                'name' => 'Nice debtor',
                'business_registration_number' => '543675645',
                'vat_number' => 'NL4354352',
                'general_phone_number' => '030525941',
                'general_mobile_phone' => '0652599841',
                'general_email' => 'hoenkb@msn.com',
            ]
        );
        $this->assertDatabaseHas(
            'debtors',
            [
                'administration_id' => 1,
                'debtor_code' => '2344',
            ]
        );
        $this->assertDatabaseHas(
            'persons',
            [
                'first_name' => 'Hoenk',
                'last_name' => 'Begoerer',
                'job_title' => 'Relschopper',
            ]
        );
        $this->assertDatabaseHas(
            'invoices',
            [
                'invoice_code' => 'GFW53-002485',
                'invoice_date' => '2025-04-05',
                'date_received' => '2026-07-01 11:22:41',
                'due_date' => '2025-02-06',
                'amount' => '6542.40',
                'outstanding_amount' => '1892.30',
                'currency' => 'EUR',
                'exchange_rate' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'invoice_notes',
            [
                'content' => 'Mooi verhaal hier',
            ]
        );

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_notes', 1);
    }

    public function test_creating_without_data(): void
    {
        $token = $this->authenticate();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer')
            ->assertUnprocessable()
            ->assertExactJson(
                [
                    'message' => 'The file field is required.',
                    'errors' => [
                        'file' => [
                            'The file field is required.',
                        ],
                    ],
                ]
            );
    }

    public function test_creating_with_wrong_data_types(): void
    {
        $token = $this->authenticate();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $uploadedFile1 = UploadedFile::fake()->create(
            'offer-34224.xlsx',
            51201,
            'application/pdf'
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $uploadedFile1])
            ->assertUnprocessable()
            ->assertExactJson(
                [
                    'message' => 'The file field must be a file of type: xlsx, xls. (and 1 more error)',
                    'errors' => [
                        'file' => [
                            'The file field must be a file of type: xlsx, xls.',
                            'The file field must not be greater than 51200 kilobytes.',
                        ],
                    ],
                ]
            );
    }

    public function test_creating_without_naw_client_id(): void
    {
        $token = $this->authenticate();
        Log::spy();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                null,
                1,
                null,
                null,
                '2344',
                'Nice debtor',
                'Straatweg 84',
                '3451 GT',
                'Amsterdam',
                'NL',
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $spreadsheet->createSheet();

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'Incorrect customer number format',
                ['customer_number' => null]
            );

        $this->assertDatabaseCount('addresses', 0);
        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('debtors', 0);
        $this->assertDatabaseCount('persons', 0);
        $this->assertDatabaseCount('company_contacts', 0);
        $this->assertDatabaseCount('debtor_contacts', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_without_naw_debtor_code(): void
    {
        $token = $this->authenticate();
        Log::spy();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                null,
                'Nice debtor',
                'Straatweg 84',
                '3451 GT',
                'Amsterdam',
                'NL',
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $spreadsheet->createSheet();

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'Invalid row detected, no debtor number found',
                [
                    'file_name' => 'testing.xlsx',
                    'row_number' => 3,
                    'customer_number' => '60341_DK',
                ]
            );

        $this->assertDatabaseCount('addresses', 0);
        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 0);
        $this->assertDatabaseCount('company_contacts', 0);
        $this->assertDatabaseCount('debtor_contacts', 0);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_notes', 1);
    }

    public function test_creating_without_naw_debtor_name(): void
    {
        $token = $this->authenticate();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                2344,
                null,
                'Straatweg 84',
                '3451 GT',
                'Amsterdam',
                'NL',
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $spreadsheet->createSheet();

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        $this->assertDatabaseCount('addresses', 0);
        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 0);
        $this->assertDatabaseCount('company_contacts', 0);
        $this->assertDatabaseCount('debtor_contacts', 0);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_notes', 1);
    }

    public function test_creating_without_naw_address_values(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                '2344',
                'Nice debtor',
                null,
                null,
                null,
                null,
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        $this->assertDatabaseHas(
            'companies',
            [
                'address_id' => null,
                'name' => 'Nice debtor',
                'business_registration_number' => '543675645',
                'vat_number' => 'NL4354352',
                'general_phone_number' => '030525941',
                'general_mobile_phone' => '0652599841',
                'general_email' => 'hoenkb@msn.com',
            ]
        );
        $this->assertDatabaseHas(
            'debtors',
            [
                'administration_id' => 1,
                'debtor_code' => '2344',
            ]
        );
        $this->assertDatabaseHas(
            'persons',
            [
                'first_name' => 'Hoenk',
                'last_name' => 'Begoerer',
                'job_title' => 'Relschopper',
            ]
        );
        $this->assertDatabaseHas(
            'invoices',
            [
                'invoice_code' => 'GFW53-002485',
                'invoice_date' => '2025-04-05',
                'date_received' => '2026-07-01 11:22:41',
                'due_date' => '2025-02-06',
                'amount' => '6542.40',
                'outstanding_amount' => '1892.30',
                'currency' => 'EUR',
                'exchange_rate' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'invoice_notes',
            [
                'content' => 'Mooi verhaal hier',
            ]
        );

        $this->assertDatabaseCount('addresses', 0);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_notes', 1);
    }

    public function test_creating_without_invoice_client_id(): void
    {
        $token = $this->authenticate();
        Log::spy();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                2344,
                'Nice Debtor',
                'Straatweg 84',
                '3451 GT',
                'Amsterdam',
                'NL',
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                null,
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $spreadsheet->createSheet();

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'Invalid row detected, incorrect client id',
                ['file_name' => 'testing.xlsx', 'row_number' => 3, 'customer_number' => '60341_DK']
            );

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_without_invoice_debtor_code(): void
    {
        $token = $this->authenticate();
        Log::spy();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                2344,
                'Nice Debtor',
                'Straatweg 84',
                '3451 GT',
                'Amsterdam',
                'NL',
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                null,
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $spreadsheet->createSheet();

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'Invalid row detected, no debtor number found',
                [
                    'file_name' => 'testing.xlsx',
                    'row_number' => 3,
                    'customer_number' => '60341_DK',
                ]
            );

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_without_invoice_invoice_code(): void
    {
        $token = $this->authenticate();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                2344,
                'Nice Debtor',
                'Straatweg 84',
                '3451 GT',
                'Amsterdam',
                'NL',
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                null,
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $spreadsheet->createSheet();

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_without_invoice_invoice_note(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                '2344',
                'Nice debtor',
                'Straatweg 84',
                '3451 GT',
                'Amsterdam',
                'NL',
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                null,
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_without_administration(): void
    {
        $token = $this->authenticate();
        Log::spy();

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                2344,
                'Nice Debtor',
                'Straatweg 84',
                '3451 GT',
                'Amsterdam',
                'NL',
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                null,
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $spreadsheet->createSheet();

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'No administration found',
                ['customer_number' => '60341_DK']
            );

        $this->assertDatabaseCount('addresses', 0);
        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('debtors', 0);
        $this->assertDatabaseCount('persons', 0);
        $this->assertDatabaseCount('company_contacts', 0);
        $this->assertDatabaseCount('debtor_contacts', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_with_non_existing_naw_client_id(): void
    {
        $token = $this->authenticate();
        Log::spy();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $spreadsheet = new Spreadsheet;

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '45687_NF',
                1,
                null,
                null,
                '2344',
                'Nice debtor',
                'Straatweg 84',
                '3451 GT',
                'Amsterdam',
                'NL',
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'No administration found',
                ['customer_number' => '45687_NF']
            );

        $this->assertDatabaseCount('addresses', 0);
        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('debtors', 0);
        $this->assertDatabaseCount('persons', 0);
        $this->assertDatabaseCount('company_contacts', 0);
        $this->assertDatabaseCount('debtor_contacts', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_with_non_existing_invoice_client_id(): void
    {
        $token = $this->authenticate();
        Log::spy();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $spreadsheet = new Spreadsheet;

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                '2344',
                'Nice debtor',
                'Straatweg 84',
                '3451 GT',
                'Amsterdam',
                'NL',
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '423243_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'Invalid row detected, incorrect client id',
                ['file_name' => 'testing.xlsx', 'row_number' => 3, 'customer_number' => '60341_DK']
            );

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_with_existing_naw_and_invoice_debtor_code(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);
        Person::insert([
            'id' => 1,
            'first_name' => 'Hoenk',
            'last_name' => 'Begoerer',
        ]);
        Address::insert([
            'id' => 1,
            'address' => 'Hoenkstraat 43',
            'zip_code' => '1337 GG',
            'city' => 'Utrecht',
            'country' => 'NL',
        ]);
        Company::insert([
            'id' => 1,
            'address_id' => 1,
            'name' => 'Hoenk B.V.',
            'general_phone_number' => '030568523',
            'general_mobile_phone' => '0658566985',
            'general_email' => 'hoenkb@msn.com',
            'business_registration_number' => '4536345632',
        ]);
        Debtor::insert([
            'id' => 1,
            'administration_id' => 1,
            'company_id' => 1,
            'debtor_code' => '2344',
        ]);
        CompanyContact::insert([
            'id' => 1,
            'company_id' => 1,
            'person_id' => 1,
        ]);
        DebtorContact::insert([
            'id' => 1,
            'debtor_id' => 1,
            'person_id' => 1,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                '2344',
                'Hoenk B.V.',
                'Hoenkstraat 43',
                '1337 GG',
                'Utrecht',
                'NL',
                null,
                'Hoenk',
                'Begoerer',
                null,
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        $this->assertDatabaseHas(
            'addresses',
            [
                'id' => 1,
                'address' => 'Hoenkstraat 43',
                'zip_code' => '1337 GG',
                'city' => 'Utrecht',
                'country' => 'NL',
            ]
        );
        $this->assertDatabaseHas(
            'companies',
            [
                'id' => 1,
                'address_id' => 1,
                'name' => 'Hoenk B.V.',
                'business_registration_number' => '543675645',
                'vat_number' => 'NL4354352',
                'general_phone_number' => '030525941',
                'general_mobile_phone' => '0652599841',
                'general_email' => 'hoenkb@msn.com',
            ]
        );
        $this->assertDatabaseHas(
            'debtors',
            [
                'id' => 1,
                'administration_id' => 1,
                'debtor_code' => '2344',
            ]
        );
        $this->assertDatabaseHas(
            'persons',
            [
                'id' => 1,
                'salutation' => null,
                'initials' => null,
                'first_name' => 'Hoenk',
                'last_name' => 'Begoerer',
                'job_title' => null,
            ]
        );
        $this->assertDatabaseHas(
            'invoices',
            [
                'debtor_id' => 1,
                'invoice_code' => 'GFW53-002485',
                'invoice_date' => '2025-04-05',
                'date_received' => '2026-07-01 11:22:41',
                'due_date' => '2025-02-06',
                'amount' => '6542.40',
                'outstanding_amount' => '1892.30',
                'currency' => 'EUR',
                'exchange_rate' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'invoice_notes',
            [
                'content' => 'Mooi verhaal hier',
            ]
        );

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_notes', 1);
    }

    public function test_creating_with_existing_naw_debtor_company_with_updated_company_data(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);
        Address::insert([
            'id' => 1,
            'address' => 'Hoenkstraat 43',
            'zip_code' => '1337 GG',
            'city' => 'Utrecht',
            'country' => 'NL',
        ]);
        Company::insert([
            'id' => 1,
            'address_id' => 1,
            'name' => 'Hoenk B.V.',
            'business_registration_number' => '4536345632',
            'general_phone_number' => '030568523',
            'general_mobile_phone' => '0658566985',
            'general_email' => 'hoenkb@msn.com',
        ]);
        Debtor::insert([
            'id' => 1,
            'administration_id' => 1,
            'company_id' => 1,
            'debtor_code' => '2344',
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                '2344',
                'Hoenk B.V.',
                'Hoenkstraat 43',
                '4325 GB',
                'Amsterdam',
                'BE',
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '0308599845',
                '0678755652',
                null,
                'goodcompany@msn.com',
                '456654762',
                null,
                null,
                '1',
                null,
                null,
                'NL7562345',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        $this->assertDatabaseHas(
            'addresses',
            [
                'id' => 1,
                'address' => 'Hoenkstraat 43',
                'zip_code' => '4325 GB',
                'city' => 'Amsterdam',
                'country' => 'BE',
            ]
        );
        $this->assertDatabaseHas(
            'companies',
            [
                'id' => 1,
                'address_id' => 1,
                'name' => 'Hoenk B.V.',
                'business_registration_number' => '456654762',
                'general_phone_number' => '0308599845',
                'general_mobile_phone' => '0678755652',
                'general_email' => 'goodcompany@msn.com',
                'vat_number' => 'NL7562345',
                'website' => null,
            ]
        );
        $this->assertDatabaseHas(
            'debtors',
            [
                'id' => 1,
                'administration_id' => 1,
                'company_id' => 1,
                'debtor_code' => '2344',
            ]
        );
        $this->assertDatabaseHas(
            'persons',
            [
                'first_name' => 'Hoenk',
                'last_name' => 'Begoerer',
                'job_title' => 'Relschopper',
            ]
        );
        $this->assertDatabaseHas(
            'company_contacts',
            [
                'company_id' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'debtor_contacts',
            [
                'debtor_id' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'invoices',
            [
                'debtor_id' => 1,
                'invoice_code' => 'GFW53-002485',
                'invoice_date' => '2025-04-05',
                'date_received' => '2026-07-01 11:22:41',
                'due_date' => '2025-02-06',
                'amount' => '6542.40',
                'outstanding_amount' => '1892.30',
                'currency' => 'EUR',
                'exchange_rate' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'invoice_notes',
            [
                'content' => 'Mooi verhaal hier',
            ]
        );

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_notes', 1);
    }

    public function test_creating_with_existing_naw_person(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);
        Person::insert([
            'id' => 1,
            'first_name' => 'Truus',
            'last_name' => 'Boom',
            'job_title' => 'Ramenlapper',
        ]);
        Address::insert([
            'id' => 1,
            'address' => 'Hoenkstraat 43',
            'zip_code' => '1337 GG',
            'city' => 'Utrecht',
            'country' => 'NL',
        ]);
        Company::insert([
            'id' => 1,
            'address_id' => 1,
            'name' => 'Hoenk B.V.',
            'business_registration_number' => '4536345632',
            'general_phone_number' => '030568523',
            'general_mobile_phone' => '0658566985',
            'general_email' => 'hoenkb@msn.com',
        ]);
        Debtor::insert([
            'id' => 1,
            'administration_id' => 1,
            'company_id' => 1,
            'debtor_code' => '2344',
        ]);
        CompanyContact::insert([
            'id' => 1,
            'company_id' => 1,
            'person_id' => 1,
        ]);
        DebtorContact::insert([
            'id' => 1,
            'debtor_id' => 1,
            'person_id' => 1,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                '2344',
                'Hoenk B.V.',
                'Hoenkstraat 43',
                '1337 GG',
                'Utrecht',
                'NL',
                null,
                'Truus',
                'Boom',
                'Ramenlapper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        $this->assertDatabaseHas(
            'addresses',
            [
                'id' => 1,
                'address' => 'Hoenkstraat 43',
                'zip_code' => '1337 GG',
                'city' => 'Utrecht',
                'country' => 'NL',
            ]
        );
        $this->assertDatabaseHas(
            'companies',
            [
                'id' => 1,
                'address_id' => 1,
                'name' => 'Hoenk B.V.',
                'business_registration_number' => '543675645',
                'vat_number' => 'NL4354352',
                'general_phone_number' => '030525941',
                'general_mobile_phone' => '0652599841',
                'general_email' => 'hoenkb@msn.com',
            ]
        );
        $this->assertDatabaseHas(
            'debtors',
            [
                'administration_id' => 1,
                'debtor_code' => '2344',
            ]
        );
        $this->assertDatabaseHas(
            'persons',
            [
                'id' => 1,
                'salutation' => null,
                'initials' => null,
                'first_name' => 'Truus',
                'last_name' => 'Boom',
                'job_title' => 'Ramenlapper',
            ]
        );
        $this->assertDatabaseHas(
            'company_contacts',
            [
                'id' => 1,
                'company_id' => 1,
                'person_id' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'debtor_contacts',
            [
                'id' => 1,
                'debtor_id' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'invoices',
            [
                'invoice_code' => 'GFW53-002485',
                'invoice_date' => '2025-04-05',
                'date_received' => '2026-07-01 11:22:41',
                'due_date' => '2025-02-06',
                'amount' => '6542.40',
                'outstanding_amount' => '1892.30',
                'currency' => 'EUR',
                'exchange_rate' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'invoice_notes',
            [
                'content' => 'Mooi verhaal hier',
            ]
        );

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_notes', 1);
    }

    public function test_creating_with_existing_naw_person_with_different_job_title(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);
        Person::insert([
            'id' => 1,
            'first_name' => 'Truus',
            'last_name' => 'Boom',
            'job_title' => 'Relschopper',
        ]);
        Address::insert([
            'id' => 1,
            'address' => 'Hoenkstraat 43',
            'zip_code' => '1337 GG',
            'city' => 'Utrecht',
            'country' => 'NL',
        ]);
        Company::insert([
            'id' => 1,
            'address_id' => 1,
            'name' => 'Hoenk B.V.',
            'business_registration_number' => '4536345632',
            'general_phone_number' => '030568523',
            'general_mobile_phone' => '0658566985',
            'general_email' => 'hoenkb@msn.com',
        ]);
        Debtor::insert([
            'id' => 1,
            'administration_id' => 1,
            'company_id' => 1,
            'debtor_code' => '2344',
        ]);
        CompanyContact::insert([
            'id' => 1,
            'company_id' => 1,
            'person_id' => 1,
        ]);
        DebtorContact::insert([
            'id' => 1,
            'debtor_id' => 1,
            'person_id' => 1,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                '2344',
                'Hoenk B.V.',
                'Hoenkstraat 43',
                '1337 GG',
                'Utrecht',
                'NL',
                null,
                'Truus',
                'Boom',
                'Ramenlapper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        $this->assertDatabaseHas(
            'addresses',
            [
                'id' => 1,
                'address' => 'Hoenkstraat 43',
                'zip_code' => '1337 GG',
                'city' => 'Utrecht',
                'country' => 'NL',
            ]
        );
        $this->assertDatabaseHas(
            'companies',
            [
                'id' => 1,
                'address_id' => 1,
                'name' => 'Hoenk B.V.',
                'business_registration_number' => '543675645',
                'vat_number' => 'NL4354352',
                'general_phone_number' => '030525941',
                'general_mobile_phone' => '0652599841',
                'general_email' => 'hoenkb@msn.com',
            ]
        );
        $this->assertDatabaseHas(
            'debtors',
            [
                'administration_id' => 1,
                'debtor_code' => '2344',
            ]
        );
        $this->assertDatabaseHas(
            'persons',
            [
                'id' => 1,
                'salutation' => null,
                'initials' => null,
                'first_name' => 'Truus',
                'last_name' => 'Boom',
                'job_title' => 'Ramenlapper',
            ]
        );
        $this->assertDatabaseHas(
            'company_contacts',
            [
                'id' => 1,
                'company_id' => 1,
                'person_id' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'debtor_contacts',
            [
                'id' => 1,
                'debtor_id' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'invoices',
            [
                'invoice_code' => 'GFW53-002485',
                'invoice_date' => '2025-04-05',
                'date_received' => '2026-07-01 11:22:41',
                'due_date' => '2025-02-06',
                'amount' => '6542.40',
                'outstanding_amount' => '1892.30',
                'currency' => 'EUR',
                'exchange_rate' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'invoice_notes',
            [
                'content' => 'Mooi verhaal hier',
            ]
        );

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_notes', 1);
    }

    public function test_creating_with_existing_naw_person_from_another_company(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);
        Person::insert([
            'id' => 1,
            'first_name' => 'Truus',
            'last_name' => 'Boom',
            'job_title' => 'Ramenlapper',
        ]);
        Address::insert([
            'id' => 1,
            'address' => 'Hoenkstraat 43',
            'zip_code' => '1337 GG',
            'city' => 'Utrecht',
            'country' => 'NL',
        ]);
        Company::insert([
            'id' => 1,
            'address_id' => 1,
            'name' => 'Hoenk B.V.',
            'business_registration_number' => '4536345632',
            'general_phone_number' => '030568523',
            'general_mobile_phone' => '0658566985',
            'general_email' => 'hoenkb@msn.com',
        ]);
        Debtor::insert([
            'id' => 1,
            'administration_id' => 1,
            'company_id' => 1,
            'debtor_code' => '2344',
        ]);
        CompanyContact::insert([
            'id' => 1,
            'company_id' => 1,
            'person_id' => 1,
        ]);
        DebtorContact::insert([
            'id' => 1,
            'debtor_id' => 1,
            'person_id' => 1,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                '2344',
                'Nice debtor',
                'Straatweg 84',
                '3451 GT',
                'Amsterdam',
                'NL',
                null,
                'Truus',
                'Boom',
                'Ramenlapper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                'NL4354352',
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');
        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        $this->assertDatabaseHas(
            'addresses',
            [
                'id' => 1,
                'address' => 'Hoenkstraat 43',
                'zip_code' => '1337 GG',
                'city' => 'Utrecht',
                'country' => 'NL',
            ]
        );
        $this->assertDatabaseHas(
            'addresses',
            [
                'address' => 'Straatweg 84',
                'zip_code' => '3451 GT',
                'city' => 'Amsterdam',
                'country' => 'NL',
            ]
        );
        $this->assertDatabaseHas(
            'companies',
            [
                'id' => 1,
                'address_id' => 1,
                'name' => 'Hoenk B.V.',
                'business_registration_number' => '4536345632',
                'vat_number' => null,
                'general_phone_number' => '030568523',
                'general_mobile_phone' => '0658566985',
                'general_email' => 'hoenkb@msn.com',
                'website' => null,
            ],
        );
        $this->assertDatabaseHas(
            'companies',
            [
                'name' => 'Nice debtor',
                'business_registration_number' => '543675645',
                'vat_number' => 'NL4354352',
                'general_phone_number' => '030525941',
                'general_mobile_phone' => '0652599841',
                'general_email' => 'hoenkb@msn.com',
                'website' => null,
            ]
        );
        $this->assertDatabaseHas(
            'debtors',
            [
                'administration_id' => 1,
                'debtor_code' => '2344',
            ]
        );
        $this->assertDatabaseHas(
            'persons',
            [
                'id' => 1,
                'salutation' => null,
                'initials' => null,
                'first_name' => 'Truus',
                'last_name' => 'Boom',
                'job_title' => 'Ramenlapper',
            ]
        );
        $this->assertDatabaseHas(
            'company_contacts',
            [
                'id' => 1,
                'person_id' => 1,
                'company_id' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'debtor_contacts',
            [
                'id' => 1,
                'debtor_id' => 1,
                'person_id' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'invoices',
            [
                'invoice_code' => 'GFW53-002485',
                'invoice_date' => '2025-04-05',
                'date_received' => '2026-07-01 11:22:41',
                'due_date' => '2025-02-06',
                'amount' => '6542.40',
                'outstanding_amount' => '1892.30',
                'currency' => 'EUR',
                'exchange_rate' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'invoice_notes',
            [
                'content' => 'Mooi verhaal hier',
            ]
        );

        $this->assertDatabaseCount('addresses', 2);
        $this->assertDatabaseCount('companies', 2);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 2);
        $this->assertDatabaseCount('company_contacts', 2);
        $this->assertDatabaseCount('debtor_contacts', 2);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_notes', 1);
    }

    public function test_creating_with_existing_invoice_code(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);
        Debtor::insert([
            'id' => 1,
            'administration_id' => 1,
            'company_id' => null,
            'debtor_code' => '2344',
        ]);
        Invoice::insert([
            'id' => 1,
            'debtor_id' => 1,
            'invoice_code' => 'GFW53-002485',
            'invoice_date' => '2026-08-05',
            'date_received' => \Illuminate\Support\Carbon::now(),
            'due_date' => '2026-08-08',
            'amount' => 651651.45,
            'outstanding_amount' => 65415.20,
            'currency' => 'EUR',
            'exchange_rate' => 1,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
                'Portefeuille',
                'Incasso schema code',
                'Rente schema code',
                'Deb.nr.',
                'Debiteurnaam',
                'Adres',
                'Postcode',
                'Plaats',
                'Land=ISO code',
                'Taal',
                'Voornaam',
                'Achternaam',
                'Functie',
                'Telefoon',
                'Mobiel',
                'Fax',
                'E-mail',
                'KvK',
                'Krediet Limiet',
                'Betalingskorting',
                'Betalingstermijn',
                'Score',
                'Link',
                'BTW nummer',
                'Financieringslimiet',
                'Kredietverzekering',
                'Titulatuur',
                'Kredietlimiet Euler',
            ],
            [
                '60341_DK',
                1,
                null,
                null,
                '2344',
                'Nice debtor',
                null,
                null,
                null,
                null,
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'hoenkb@msn.com',
                '543675645',
                null,
                null,
                '1',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ],
        ], null, 'A2');

        // ---- Sheet 2 ----
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->setActiveSheetIndex(1);
        $sheet2->setTitle('Factuurgegevens');
        $sheet2->fromArray([
            [
                'Client ID',
                'Debiteurnummer',
                'Factuurnummer',
                'Factuurdatum',
                'vervaldatum',
                'bedrag',
                'Openstaand bedrag ',
                'Omschrijving',
                'Valuta',
                'Koers',
            ],
            [
                '60341_DK',
                '2344',
                'GFW53-002485',
                '5-04-2025',
                '06-02-25',
                '6542,4',
                '1892,3',
                'Mooi verhaal hier',
                'EUR',
                '1',
            ],
        ], null, 'A2');

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/deed-of-transfer', ['file' => $file])
            ->assertOk();

        unlink($path);

        $this->assertDatabaseHas(
            'debtors',
            [
                'id' => 1,
                'administration_id' => 1,
                'debtor_code' => '2344',
            ]
        );
        $this->assertDatabaseHas(
            'invoices',
            [
                'id' => 1,
                'debtor_id' => 1,
                'invoice_code' => 'GFW53-002485',
                'invoice_date' => '2026-08-05',
                'date_received' => '2026-07-01 11:22:41',
                'due_date' => '2026-08-08',
                'amount' => 651651.45,
                'outstanding_amount' => 65415.20,
                'currency' => 'EUR',
                'exchange_rate' => 1,
            ]
        );

        $this->assertDatabaseCount('addresses', 0);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_returns_spreadsheet_error(): void
    {
        $token = $this->authenticate();
        Log::spy();
        Excel::shouldReceive('import')
            ->once()
            ->andThrow(new SpreadsheetException);
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
            ],
            [
                '60341_DK',
            ],
        ], null, 'A2');

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            null,
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson(
                '/api/import/deed-of-transfer',
                [
                    'file' => $file,
                ]
            )
            ->assertOk();

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'The uploaded file is not a valid Excel document',
                ['file_name' => 'testing.xlsx', 'customer_number' => '60341_DK']
            );
    }

    public function test_creating_returns_generic_error(): void
    {
        $token = $this->authenticate();
        Log::spy();
        Excel::fake();
        Excel::shouldReceive('import')
            ->once()
            ->andThrow(new \RuntimeException);
        Portfolio::insert(['id' => 1, 'name' => 'Portfolio de Grande']);
        Administration::insert([
            'id' => 1,
            'portfolio_id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $spreadsheet = new Spreadsheet;

        // ---- Sheet 1 ----
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('NAW-gegevens');
        $sheet1->fromArray([
            [
                'Administratie',
            ],
            [
                '60341_DK',
            ],
        ], null, 'A2');

        $path = storage_path('testing.xlsx');
        new Xlsx($spreadsheet)->save($path);

        $file = new UploadedFile(
            $path,
            'testing.xlsx',
            null,
            null,
            true
        );

        $this->withHeader('Authorization', $token)
            ->postJson(
                '/api/import/deed-of-transfer',
                ['file' => $file]
            )
            ->assertOk();

        Log::shouldHaveReceived('error')
            ->atLeast()
            ->once()
            ->with(
                'Failed to import the file',
                ['file_name' => 'testing.xlsx', 'customer_number' => '60341_DK']
            );

        unlink($path);
    }
}
