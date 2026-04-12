<?php

declare(strict_types=1);

namespace tests\Imports;

use Tests\TestCase;

final class ImportDBasicsDebtorTest extends TestCase
{
    public function test_creating_unauthenticated(): void
    {
        $this->postJson('/api/import/d-basics/debtors')->assertUnauthorized();
    }

    public function test_creating_with_correct_data(): void
    {
        $token = $this->authenticate();
        Administration::insert([
            'id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
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
            '60341_DK-file-with-import-data.txt',
            $content
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/debtors', ['file' => $file])
            ->assertOk();

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

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
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
            ->postJson('/api/import/d-basics/debtors')
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
            ->postJson('/api/import/d-basics/debtors', ['file' => $uploadedFile1])
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
            ->postJson('/api/import/d-basics/debtors', ['file' => $file])
            ->assertOk();

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
            '60341_DK-file-with-import-data.txt',
            $content
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/debtors', ['file' => $file])
            ->assertOk();

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'Invalid row detected, no debtor number found',
                [
                    'file_name' => '60341_DK-file-with-import-data.txt',
                    'row_number' => 2,
                    'customer_number' => '60341_DK',
                ]
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
            '60341_DK-file-with-import-data.txt',
            $content
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/debtors', ['file' => $file])
            ->assertOk();

        $this->assertDatabaseCount('addresses', 0);
        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('debtors', 0);
        $this->assertDatabaseCount('persons', 0);
        $this->assertDatabaseCount('company_contacts', 0);
        $this->assertDatabaseCount('debtor_contacts', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_without_address_values(): void
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
            '60341_DK-file-with-import-data.txt',
            $content
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/debtors', ['file' => $file])
            ->assertOk();

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

        $this->assertDatabaseCount('addresses', 0);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_without_administration(): void
    {
        $token = $this->authenticate();
        Log::spy();

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
            '60341_DK-file-with-import-data.txt',
            $content
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/debtors', ['file' => $file])
            ->assertOk();

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

    public function test_creating_with_non_existing_client_id(): void
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
            '65443-file-with-import-data.txt',
            $content
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/debtors', ['file' => $file])
            ->assertOk();

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'No administration found',
                ['customer_number' => '65443']
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

    public function test_creating_with_existing_debtor_company_with_updated_company_data(): void
    {
        $token = $this->authenticate();
        Administration::insert([
            [
                'id' => 1,
                'customer_number' => '60341_DK',
                'active' => true,
            ],
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
                'Hoenk B.V.',
                'Hoenkstraat 43',
                '3451 GT',
                'Amsterdam',
                'BE',
                null,
                'Hoenk',
                'Begoerer',
                'Relschopper',
                '030525941',
                '0652599841',
                null,
                'truusb@msn.com',
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
            '60341_DK-file-with-import-data.txt',
            $content
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/debtors', ['file' => $file])
            ->assertOk();

        $this->assertDatabaseHas(
            'addresses',
            [
                'id' => 1,
                'address' => 'Hoenkstraat 43',
                'zip_code' => '3451 GT',
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
                'business_registration_number' => '543675645',
                'general_phone_number' => '030525941',
                'general_mobile_phone' => '0652599841',
                'general_email' => 'truusb@msn.com',
                'vat_number' => 'NL4354352',
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

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_with_existing_person(): void
    {
        $token = $this->authenticate();
        Administration::insert([
            [
                'id' => 1,
                'customer_number' => '60341_DK',
                'active' => true,
            ],
        ]);
        Person::insert([
            'id' => 1,
            'first_name' => 'Truus',
            'last_name' => 'Boom',
            'job_title' => 'Tuinier',
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
                'Hoenk B.V.',
                'Hoenkstraat 43',
                '1337 GG',
                'Utrecht',
                'NL',
                null,
                'Truus',
                'Boom',
                'Tuinier',
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
            '60341_DK-file-with-import-data.txt',
            $content
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/debtors', ['file' => $file])
            ->assertOk();

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
                'job_title' => 'Tuinier',
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

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_with_existing_person_with_different_job_title(): void
    {
        $token = $this->authenticate();
        Administration::insert([
            [
                'id' => 1,
                'customer_number' => '60341_DK',
                'active' => true,
            ],
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
                'Hoenk B.V.',
                'Hoenkstraat 43',
                '1337 GG',
                'Utrecht',
                'NL',
                null,
                'Truus',
                'Boom',
                'Tuinier',
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
            '60341_DK-file-with-import-data.txt',
            $content
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/debtors', ['file' => $file])
            ->assertOk();

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
                'job_title' => 'Tuinier',
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

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('company_contacts', 1);
        $this->assertDatabaseCount('debtor_contacts', 1);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_notes', 0);
    }

    public function test_creating_with_existing_person_from_another_company(): void
    {
        $token = $this->authenticate();
        Administration::insert([
            [
                'id' => 1,
                'customer_number' => '60341_DK',
                'active' => true,
            ],
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
                'Nice Debtor',
                'Hoenkstraat 43',
                '1337 GG',
                'Utrecht',
                'NL',
                null,
                'Truus',
                'Boom',
                'Tuinier',
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
            '60341_DK-file-with-import-data.txt',
            $content
        );

        $this->withHeader('Authorization', $token)
            ->postJson('/api/import/d-basics/debtors', ['file' => $file])
            ->assertOk();

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

        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('companies', 2);
        $this->assertDatabaseCount('debtors', 1);
        $this->assertDatabaseCount('persons', 2);
        $this->assertDatabaseCount('company_contacts', 2);
        $this->assertDatabaseCount('debtor_contacts', 2);
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
                ValidationException::withMessages([
                    'file' => ['Invalid data'],
                ])
            );
        Administration::insert([
            'id' => 1,
            'customer_number' => '60341_DK',
            'active' => true,
        ]);

        $this->withHeader('Authorization', $token)
            ->postJson(
                '/api/import/d-basics/debtors',
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
                '/api/import/d-basics/debtors',
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
                '/api/import/d-basics/debtors',
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
