<?php

declare(strict_types=1);

namespace tests\Imports;

use Tests\TestCase;

final class ImportDBasicsInvoiceTest extends TestCase
{
    public function test_creating_unauthenticated(): void
    {
        $this->postJson('/api/import/d-basics/invoices')->assertUnauthorized();
    }

    public function test_creating_with_correct_data(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Administration::insert([
            [
                'id' => 1,
                'customer_number' => '60341_DK',
                'active' => true,
            ],
        ]);

        $headers = [
            'DebiteurNummer',
            'FactuurNummer',
            'FactuurDatum',
            'VervalDatum',
            'FactuurBedrag',
            'OpenstaandBedrag',
            'Omschrijving',
            'Valuta',
            'Koers',
        ];
        $rows = [
            [
                '180344',
                '2501068',
                '2025-06-25',
                '2025-07-25',
                '101.37',
                '346553.54',
                'Mooi verhaal hier',
                'EUR',
                '1.00000',
            ],
            [
                '180261',
                '2501318',
                '2025-08-07',
                '2025-09-06',
                '402.21',
                '56165.23',
                'Nog mooi verhaal',
                'USD',
                '1.00000',
            ],
        ];

        $lines = [];
        $lines[] = implode("\t", $headers);
        foreach ($rows as $row) {
            $lines[] = implode("\t", $row);
        }

        $content = implode("\n", $lines);

        $file = UploadedFile::fake()->createWithContent('60341_DK-import-data.txt', $content);

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/invoices', ['file' => $file])
            ->assertOk();

        $this->assertDatabaseHas(
            'debtors',
            [
                'administration_id' => 1,
                'debtor_code' => '180344',
            ]
        );
        $this->assertDatabaseHas(
            'debtors',
            [
                'administration_id' => 1,
                'debtor_code' => '180261',
            ]
        );
        $this->assertDatabaseHas(
            'invoices',
            [
                'invoice_code' => '2501068',
                'invoice_date' => '2025-06-25',
                'date_received' => '2026-07-01 11:22:41',
                'due_date' => '2025-07-25',
                'amount' => '101.37',
                'outstanding_amount' => '346553.54',
                'currency' => 'EUR',
                'exchange_rate' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'invoices',
            [
                'invoice_code' => '2501318',
                'invoice_date' => '2025-08-07',
                'date_received' => '2026-07-01 11:22:41',
                'due_date' => '2025-09-06',
                'amount' => '402.21',
                'outstanding_amount' => '56165.23',
                'currency' => 'USD',
                'exchange_rate' => 1,
            ]
        );
        $this->assertDatabaseHas(
            'invoice_notes',
            [
                'content' => 'Mooi verhaal hier',
            ]
        );
        $this->assertDatabaseHas(
            'invoice_notes',
            [
                'content' => 'Nog mooi verhaal',
            ]
        );

        $this->assertDatabaseCount('debtors', 2);
        $this->assertDatabaseCount('invoices', 2);
        $this->assertDatabaseCount('invoice_notes', 2);
    }

    public function test_creating_without_data(): void
    {
        $token = $this->authenticate();
        Administration::insert([
            [
                'id' => 1,
                'customer_number' => '60341_DK',
                'active' => true,
            ],
        ]);

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/invoices')
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
        Administration::insert([
            [
                'id' => 1,
                'customer_number' => '60341_DK',
                'active' => true,
            ],
        ]);

        $uploadedFile1 = UploadedFile::fake()->create(
            'import.txt',
            51201,
            'application/pdf'
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/invoices', ['file' => $uploadedFile1])
            ->assertUnprocessable()
            ->assertExactJson(
                [
                    'message' => 'The file field must be a file of type: txt. (and 1 more error)',
                    'errors' => [
                        'file' => [
                            'The file field must be a file of type: txt.',
                            'The file field must not be greater than 51200 kilobytes.',
                        ],
                    ],
                ]
            );
    }

    public function test_creating_without_client_id(): void
    {
        $token = $this->authenticate();
        Log::spy();
        Administration::insert([
            [
                'id' => 1,
                'customer_number' => '60341_DK',
                'active' => true,
            ],
        ]);

        $headers = [
            'DebiteurNummer',
            'DebiteurNaam',
            'Adres',
            'Postcode',
            'Plaats',
            'Land',
            'Taal',
            'Voornaam',
            'Achternaam',
            'Functie',
            'Telefoon',
            'Mobiel',
            'Fax',
            'E-mail',
            'KVK',
            'BTW nummer',
            'Titulatuur',
        ];
        $rows = [
            [
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
                'NL4354352',
                null,
            ],
        ];

        $lines = [];
        $lines[] = implode("\t", $headers);
        foreach ($rows as $row) {
            $lines[] = implode("\t", $row);
        }

        $content = implode("\n", $lines);

        $file = UploadedFile::fake()->createWithContent(
            'file_with_import_data.txt',
            $content
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/invoices', ['file' => $file])
            ->assertOk();

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'Incorrect customer number format',
                ['customer_number' => null]
            );

        $this->assertDatabaseCount('debtors', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_without_debtor_code(): void
    {
        $token = $this->authenticate();
        Log::spy();
        Administration::insert([
            [
                'id' => 1,
                'customer_number' => '60341_DK',
                'active' => true,
            ],
        ]);

        $headers = [
            'DebiteurNummer',
            'FactuurNummer',
            'FactuurDatum',
            'VervalDatum',
            'FactuurBedrag',
            'OpenstaandBedrag',
            'Omschrijving',
            'Valuta',
            'Koers',
        ];
        $rows = [
            [
                null,
                '2501068',
                '2025-06-25',
                '2025-07-25',
                '101.37',
                '346553.54',
                'Mooi verhaal hier',
                'EUR',
                '1.00000',
            ],
        ];

        $lines = [];
        $lines[] = implode("\t", $headers);
        foreach ($rows as $row) {
            $lines[] = implode("\t", $row);
        }

        $content = implode("\n", $lines);

        $file = UploadedFile::fake()->createWithContent('60341_DK-import-data.txt', $content);

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/invoices', ['file' => $file])
            ->assertOk();

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'Invalid row detected, no debtor number found',
                [
                    'file_name' => '60341_DK-import-data.txt',
                    'row_number' => 2,
                    'customer_number' => '60341_DK',
                ]
            );

        $this->assertDatabaseCount('debtors', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_without_debtor_name(): void
    {
        $token = $this->authenticate();
        Administration::insert([
            [
                'id' => 1,
                'customer_number' => '60341_DK',
                'active' => true,
            ],
        ]);

        $headers = [
            'DebiteurNummer',
            'FactuurNummer',
            'FactuurDatum',
            'VervalDatum',
            'FactuurBedrag',
            'OpenstaandBedrag',
            'Omschrijving',
            'Valuta',
            'Koers',
        ];
        $rows = [
            [
                '180344',
                null,
                '2025-06-25',
                '2025-07-25',
                '101.37',
                '346553.54',
                'Mooi verhaal hier',
                'EUR',
                '1.00000',
            ],
        ];

        $lines = [];
        $lines[] = implode("\t", $headers);
        foreach ($rows as $row) {
            $lines[] = implode("\t", $row);
        }

        $content = implode("\n", $lines);

        $file = UploadedFile::fake()->createWithContent('60341_DK-import-data.txt', $content);

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/invoices', ['file' => $file])
            ->assertOk();

        $this->assertDatabaseCount('debtors', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_without_invoice_code(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Administration::insert([
            [
                'id' => 1,
                'customer_number' => '60341_DK',
                'active' => true,
            ],
        ]);

        $headers = [
            'DebiteurNummer',
            'FactuurNummer',
            'FactuurDatum',
            'VervalDatum',
            'FactuurBedrag',
            'OpenstaandBedrag',
            'Omschrijving',
            'Valuta',
            'Koers',
        ];
        $rows = [
            [
                '180344',
                null,
                '2025-06-25',
                '2025-07-25',
                '101.37',
                '346553.54',
                'Mooi verhaal hier',
                'EUR',
                '1.00000',
            ]
        ];

        $lines = [];
        $lines[] = implode("\t", $headers);
        foreach ($rows as $row) {
            $lines[] = implode("\t", $row);
        }

        $content = implode("\n", $lines);

        $file = UploadedFile::fake()->createWithContent('60341_DK-import-data.txt', $content);

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/invoices', ['file' => $file])
            ->assertOk();

        $this->assertDatabaseCount('debtors', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_without_invoice_note(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Administration::insert([
            [
                'id' => 1,
                'customer_number' => '60341_DK',
                'active' => true,
            ],
        ]);

        $headers = [
            'DebiteurNummer',
            'FactuurNummer',
            'FactuurDatum',
            'VervalDatum',
            'FactuurBedrag',
            'OpenstaandBedrag',
            'Omschrijving',
            'Valuta',
            'Koers',
        ];
        $rows = [
            [
                '180344',
                '2501068',
                '2025-06-25',
                '2025-07-25',
                '101.37',
                '346553.54',
                null,
                'EUR',
                '1.00000',
            ],
        ];

        $lines = [];
        $lines[] = implode("\t", $headers);
        foreach ($rows as $row) {
            $lines[] = implode("\t", $row);
        }

        $content = implode("\n", $lines);

        $file = UploadedFile::fake()->createWithContent('60341_DK-import-data.txt', $content);

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/invoices', ['file' => $file])
            ->assertOk();

        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_without_administration(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Log::spy();

        $headers = [
            'DebiteurNummer',
            'FactuurNummer',
            'FactuurDatum',
            'VervalDatum',
            'FactuurBedrag',
            'OpenstaandBedrag',
            'Omschrijving',
            'Valuta',
            'Koers',
        ];
        $rows = [
            [
                '180344',
                '2501068',
                '2025-06-25',
                '2025-07-25',
                '101.37',
                '346553.54',
                'Mooi verhaal hier',
                'EUR',
                '1.00000',
            ],
        ];

        $lines = [];
        $lines[] = implode("\t", $headers);
        foreach ($rows as $row) {
            $lines[] = implode("\t", $row);
        }

        $content = implode("\n", $lines);

        $file = UploadedFile::fake()->createWithContent('60341_DK-import-data.txt', $content);

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/invoices', ['file' => $file])
            ->assertOk();

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'No administration found',
                ['customer_number' => '60341_DK']
            );

        $this->assertDatabaseCount('debtors', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_with_non_existing_client_id(): void
    {
        $dateTime = CarbonImmutable::make('2026-07-01 11:22:41');
        Carbon::setTestNow($dateTime);
        $token = $this->authenticate();
        Log::spy();
        Administration::insert([
            [
                'id' => 1,
                'customer_number' => '60341_DK',
                'active' => true,
            ],
        ]);

        $headers = [
            'DebiteurNummer',
            'FactuurNummer',
            'FactuurDatum',
            'VervalDatum',
            'FactuurBedrag',
            'OpenstaandBedrag',
            'Omschrijving',
            'Valuta',
            'Koers',
        ];
        $rows = [
            [
                '180344',
                '2501068',
                '2025-06-25',
                '2025-07-25',
                '101.37',
                '346553.54',
                'Mooi verhaal hier',
                'EUR',
                '1.00000',
            ]
        ];

        $lines = [];
        $lines[] = implode("\t", $headers);
        foreach ($rows as $row) {
            $lines[] = implode("\t", $row);
        }

        $content = implode("\n", $lines);

        $file = UploadedFile::fake()->createWithContent('468329-import-data.txt', $content);

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/invoices', ['file' => $file])
            ->assertOk();

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'No administration found',
                ['customer_number' => '468329']
            );

        $this->assertDatabaseCount('debtors', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_returns_validation_error(): void
    {
        $token = $this->authenticate();
        Log::spy();
        Excel::fake();
        Excel::shouldReceive('import')
            ->once()
            ->andThrow(
                ValidationException::withMessages([])
            );
        Administration::insert([
            'id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $this->withHeader('Authorization', $token)
            ->postJson(
                '/api/import/d-basics/invoices',
                ['file' => UploadedFile::fake()->create('60341_DK-test.txt')]
            )
            ->assertOk();

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'The Excel file contains invalid data',
                ['file_name' => '60341_DK-test.txt', 'customer_number' => '60341_DK']
            );
    }

    public function test_creating_returns_spreadsheet_error(): void
    {
        $token = $this->authenticate();
        Log::spy();
        Excel::fake();
        Excel::shouldReceive('import')
            ->once()
            ->andThrow(new SpreadsheetException);
        Administration::insert([
            'id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $this->withHeader('Authorization', $token)
            ->postJson(
                '/api/import/d-basics/invoices',
                ['file' => UploadedFile::fake()->create('60341_DK-test.txt')]
            )
            ->assertOk();

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'The uploaded file is not a valid Excel document',
                ['file_name' => '60341_DK-test.txt', 'customer_number' => '60341_DK']
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
        Administration::insert([
            'id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $this->withHeader('Authorization', $token)
            ->postJson(
                '/api/import/d-basics/invoices',
                ['file' => UploadedFile::fake()->create('60341_DK-test.txt')]
            )
            ->assertOk();

        Log::shouldHaveReceived('error')
            ->atLeast()
            ->once()
            ->with(
                'Failed to import the file',
                ['file_name' => '60341_DK-test.txt', 'customer_number' => '60341_DK']
            );
    }
}
