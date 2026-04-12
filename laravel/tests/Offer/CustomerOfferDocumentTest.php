<?php

declare(strict_types=1);

namespace tests\Offer;

use Tests\CustomerCreditProposal;
use Tests\TestCase;

final class CustomerOfferDocumentTest extends TestCase
{
    private object $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
    }

    public function test_email_offer_document_unauthenticated(): void
    {
        $salesStatus = $this->createCustomer();
        $this->postJson(
            '/api/v1/sales_statuses/' . $salesStatus->id . '/offer/informal/document/email'
        )->assertUnauthorized();
    }

    public function test_email_offer_document_with_correct_data(): void
    {
        $dateTime = CarbonImmutable::make('2023-07-09 09:45:12');
        Carbon::setTestNow($dateTime);
        $this->actingAs($this->user);
        Storage::fake('local');
        Mail::fake();
        Address::query()->insert([
            'id' => 1,
        ]);
        SalesStatus::query()->insert(
            [
                'id' => 1,
                'currency' => 'EUR',
                'correspondence_language' => 'nl_NL',
                'customer_status_id' => 1,
            ]
        );
        Company::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => 1,
                'address_id' => 1,
                'type' => 'PROSPECT',
                'name' => 'Impact Factoring B.V.',
            ]
        );
        Person::query()->create(
            [
                'last_name' => Factory::create('EN')->lastName,
            ]
        );
        $person1 = $this->createContactPerson();
        $person2 = $this->createContactPerson();
        $person3 = $this->createContactPerson();
        $person4 = $this->createContactPerson();
        $person5 = $this->createContactPerson();
        $person6 = Person::query()->create(
            [
                'last_name' => 'Fred',
            ]
        );
        Intermediary::query()->insert(
            [
                'id' => 1,
                'person_id' => $person6->id,
            ]
        );
        $person1->companyPersonContact->contactDetail()->create(
            [
                'contactable_id' => $person1->companyPersonContact->id,
                'contactable_type' => 'company_person_contacts',
                'email_address' => 'lamar@brug.nl',
                'phone_primair' => '32132234',
            ]
        );
        $person2->companyPersonContact->contactDetail()->create(
            [
                'contactable_id' => $person2->companyPersonContact->id,
                'contactable_type' => 'company_person_contacts',
                'email_address' => 'jaat@waarold.nl',
                'phone_primair' => '53453423',
            ]
        );
        $person3->companyPersonContact->contactDetail()->create(
            [
                'contactable_id' => $person3->companyPersonContact->id,
                'contactable_type' => 'company_person_contacts',
                'email_address' => 'teef@henf.nl',
                'phone_primair' => '765576546',
            ]
        );
        $person4->companyPersonContact->contactDetail()->create(
            [
                'contactable_id' => $person4->companyPersonContact->id,
                'contactable_type' => 'company_person_contacts',
                'email_address' => 'kaas@test.nl',
                'phone_primair' => '325465',
            ]
        );
        $person5->companyPersonContact->contactDetail()->create(
            [
                'contactable_id' => $person5->companyPersonContact->id,
                'contactable_type' => 'company_person_contacts',
                'email_address' => 'jarold@boe.nl',
                'phone_primair' => '67565',
            ]
        );
        $person6->contactDetail()->create(
            [
                'contactable_id' => $person6->id,
                'contactable_type' => 'persons',
                'email_address' => 'fred@has.nl',
                'phone_primair' => '2341',
            ]
        );

        CustomerCreditProposal::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => 1,
            ],
        );

        $emailData = [
            'to' => [$person3->id, $person2->id, $person6->id],
            'cc' => [$person1->id, $person4->id, $person6->id],
            'bcc' => [$person5->id, $person6->id],
            'message' => 'Mooi offerte',
        ];

        Http::fake([
            config('impact-factoring-documents-client.base_uri') . '/api/v1/documents' => function (Request $request) {
                self::assertSame('POST', $request->method());

                return Http::response([
                    'id' => 1,
                    'original_filename' => 'offerte-IF-2025-0001-0001.pdf',
                    'hashed_filename' => 'shrtrthtrhsyser5htryjshddtyj.pdf',
                    'path' => 'test/shrtrthtrhsyser5htryjshddtyj.pdf',
                    'file_size' => 5436,
                    'mime_type' => 'application/pdf',
                ]);
            },
            config('impact-factoring-documents-client.base_uri') . '/api/v1/documents/*/download' => Http::response(
                'fake file contents',
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="offerte-impact-factoring-b.v..pdf"',
                ]
            ),
        ]);

        $response = $this->postJson('/api/v1/sales_statuses/1/offer/informal/document/email', $emailData);

        $offerDocumentResponseData = json_decode($response->getContent());

        $response
            ->assertOk()
            ->assertJsonCount(2, 'sales_status.customer_log')
            ->assertJson(
                [
                    'data' => [
                        'id' => $offerDocumentResponseData->data->id,
                        'user_id' => $this->user->id,
                        'category' => 'OFFER',
                        'file_name' => 'offerte-impact-factoring-b.v..pdf',
                        'hash_name' => 'shrtrthtrhsyser5htryjshddtyj.pdf',
                        'size' => 5436,
                        'path' => 'test/shrtrthtrhsyser5htryjshddtyj.pdf',
                        'url' => '/shrtrthtrhsyser5htryjshddtyj.pdf',
                        'mime_type' => 'application/pdf',
                        'locked' => true,
                        'file_id' => $offerDocumentResponseData->data->file_id,
                        'file_type' => 'customer_documents',
                        'created_at' => '2023-07-09T09:45:12.000000Z',
                        'updated_at' => '2023-07-09T09:45:12.000000Z',
                    ],
                    'sales_status' => [
                        'id' => 1,
                        'customer_status_id' => 1,
                        'currency' => 'EUR',
                        'customer_log' => [
                            [
                                'sales_status_id' => 1,
                                'user_id' => $this->user->id,
                                'action' => 'EVENT',
                                'title' => 'Offerte document is aangemaakt',
                                'message' => 'Offerte document offerte-IF-2025-0001-0001.pdf is aangemaakt',
                            ],
                            [
                                'sales_status_id' => 1,
                                'user_id' => $this->user->id,
                                'action' => 'EVENT',
                                'title' => 'Offerte document is verstuurd',
                                'message' => 'van: sales@impactfactoring.nl' . "\r\n" . 'naar: ' . $person3->companyPersonContact->contactDetail->email_address . ', ' . $person2->companyPersonContact->contactDetail->email_address . ', ' . $person6->contactDetail->email_address . "\r\n" . 'cc: ' . $person1->companyPersonContact->contactDetail->email_address . ', ' . $person4->companyPersonContact->contactDetail->email_address . ', ' . $person6->contactDetail->email_address . "\r\n" . 'bcc: ' . $person5->companyPersonContact->contactDetail->email_address . ', ' . $person6->contactDetail->email_address . ', offer@impactfactoring.nl' . "\r\n" . 'bijlage: offerte-impact-factoring-b.v..pdf' . "\r\n" . 'onderwerp: Offerte Impact Factoring' . "\r\n" . 'bericht:' . "\r\n\r\n" . 'Mooi offerte',
                            ],
                        ],
                    ],
                ]
            );

        Mail::assertSent(
            EmailMailable::class,
            function ($email) use (
                $offerDocumentResponseData,
                $person1,
                $person2,
                $person3,
                $person4,
                $person5,
                $person6
            ) {
                $emailContent = $email->content();

                return
                    $email->assertFrom('sales@impactfactoring.nl') &&
                    $email->assertTo($person2->companyPersonContact->contactDetail->email_address) &&
                    $email->assertTo($person3->companyPersonContact->contactDetail->email_address) &&
                    $email->assertTo($person6->contactDetail->email_address) &&
                    $email->assertHasCc($person1->companyPersonContact->contactDetail->email_address) &&
                    $email->assertHasCc($person4->companyPersonContact->contactDetail->email_address) &&
                    $email->assertHasCc($person6->contactDetail->email_address) &&
                    $email->assertHasBcc($person5->companyPersonContact->contactDetail->email_address) &&
                    $email->assertHasBcc($person6->contactDetail->email_address) &&
                    $email->assertHasBcc('offer@impactfactoring.nl') &&
                    $email->assertHasReplyTo($this->user->email) &&
                    $email->assertHasSubject('Offerte Impact Factoring') &&
                    $email->diskAttachments[0]['path'] === 'private/temporary_file_' . $offerDocumentResponseData->data->id . '.pdf' &&
                    $email->diskAttachments[0]['name'] === 'offerte-impact-factoring-b.v..pdf' &&
                    $emailContent->with['message'] === 'Mooi offerte';
            }
        );

        $this->assertEmpty(Storage::disk('local')->allFiles());

        $this->assertDatabaseHas('files', [
            'id' => $offerDocumentResponseData->data->id,
            'documents_id' => 1,
            'user_id' => $this->user->id,
            'file_type' => 'customer_documents',
            'file_id' => $offerDocumentResponseData->data->file_id,
            'category' => 'OFFER',
            'virtual_path' => 'documents/prospects/1/offer',
            'locked' => true,
            'created_at' => '2023-07-09T09:45:12.000000Z',
            'updated_at' => '2023-07-09T09:45:12.000000Z',
        ]);
        $this->assertDatabaseHas('customer_log', [
            'sales_status_id' => 1,
            'user_id' => $this->user->id,
            'action' => 'EVENT',
            'title' => 'Offerte document is aangemaakt',
            'message' => 'Offerte document offerte-IF-2025-0001-0001.pdf is aangemaakt',
        ]);
        $this->assertDatabaseHas('customer_log', [
            'sales_status_id' => 1,
            'user_id' => $this->user->id,
            'action' => 'EVENT',
            'title' => 'Offerte document is verstuurd',
            'message' => 'van: sales@impactfactoring.nl' . "\r\n" . 'naar: ' . $person3->companyPersonContact->contactDetail->email_address . ', ' . $person2->companyPersonContact->contactDetail->email_address . ', ' . $person6->contactDetail->email_address . "\r\n" . 'cc: ' . $person1->companyPersonContact->contactDetail->email_address . ', ' . $person4->companyPersonContact->contactDetail->email_address . ', ' . $person6->contactDetail->email_address . "\r\n" . 'bcc: ' . $person5->companyPersonContact->contactDetail->email_address . ', ' . $person6->contactDetail->email_address . ', offer@impactfactoring.nl' . "\r\n" . 'bijlage: offerte-impact-factoring-b.v..pdf' . "\r\n" . 'onderwerp: Offerte Impact Factoring' . "\r\n" . 'bericht:' . "\r\n\r\n" . 'Mooi offerte',
        ]);

        $this->assertDatabaseCount('files', 1);
        $this->assertDatabaseCount('customer_log', 2);
    }

    public function test_email_offer_document_without_data(): void
    {
        $this->actingAs($this->user);
        $salesStatus = $this->createCustomer();

        $this->postJson('/api/v1/sales_statuses/' . $salesStatus->id . '/offer/formal/document/email')
            ->assertUnprocessable()
            ->assertExactJson(
                [
                    'message' => 'The given data was invalid',
                    'errors' => [
                        'to' => [
                            'veld is verplicht',
                        ],
                    ],
                ]
            );
    }

    public function test_email_offer_document_with_wrong_data_types(): void
    {
        $this->actingAs($this->user);
        $salesStatus = $this->createCustomer();

        $emailData = [
            'to' => ['to'],
            'cc' => ['cc person'],
            'bcc' => [false],
            'message' => 65,
        ];

        $this->postJson(
            '/api/v1/sales_statuses/' . $salesStatus->id . '/offer/real/document/email',
            $emailData
        )
            ->assertUnprocessable()
            ->assertExactJson(
                [
                    'message' => 'The given data was invalid',
                    'errors' => [
                        'type' => [
                            'geselecteerde waarde is ongeldig',
                        ],
                        'to' => [
                            [
                                'moet een heel getal zijn',
                            ],
                        ],
                        'cc' => [
                            [
                                'moet een heel getal zijn',
                            ],
                        ],
                        'bcc' => [
                            [
                                'moet een heel getal zijn',
                            ],
                        ],
                        'message' => [
                            'moet een tekst zijn',
                        ],
                    ],
                ]
            );
    }

    public function test_email_offer_document_with_wrong_array_data_types(): void
    {
        $this->actingAs($this->user);
        $salesStatus = $this->createCustomer();

        $emailData = [
            'to' => true,
            'cc' => 'cc person',
            'bcc' => false,
        ];

        $this->postJson(
            '/api/v1/sales_statuses/' . $salesStatus->id . '/offer/informal/document/email',
            $emailData
        )
            ->assertUnprocessable()
            ->assertExactJson(
                [
                    'message' => 'The given data was invalid',
                    'errors' => [
                        'to' => [
                            'moet een array zijn',
                        ],
                        'cc' => [
                            'moet een array zijn',
                        ],
                        'bcc' => [
                            'moet een array zijn',
                        ],
                    ],
                ]
            );
    }

    public function test_email_offer_document_with_non_existing_sales_status_id(): void
    {
        $this->actingAs($this->user);
        $this->postJson('/api/v1/sales_statuses/1/offer/informal/document/email')->assertNotFound();
    }

    public function test_email_offer_document_with_non_existing_foreign_ids(): void
    {
        $this->actingAs($this->user);
        $salesStatus = $this->createCustomer();

        $emailData = [
            'to' => [1],
            'cc' => [1],
            'bcc' => [1],
        ];

        $this->postJson(
            '/api/v1/sales_statuses/' . $salesStatus->id . '/offer/informal/document/email',
            $emailData
        )
            ->assertUnprocessable()
            ->assertExactJson(
                [
                    'message' => 'The given data was invalid',
                    'errors' => [
                        'to' => [
                            [
                                'geselecteerde waarde is ongeldig',
                            ],
                        ],
                        'cc' => [
                            [
                                'geselecteerde waarde is ongeldig',
                            ],
                        ],
                        'bcc' => [
                            [
                                'geselecteerde waarde is ongeldig',
                            ],
                        ],
                    ],
                ]
            );
    }

    public function test_email_offer_document_with_non_existing_contact_person_or_intermediary(): void
    {
        $this->actingAs($this->user);
        $salesStatus = $this->createCustomer();

        Person::query()->insert(
            [
                'id' => 1,
                'last_name' => 'Haas',
            ]
        );

        $emailData = [
            'to' => [1],
            'cc' => [1],
            'bcc' => [1],
        ];

        $this->postJson(
            '/api/v1/sales_statuses/' . $salesStatus->id . '/offer/informal/document/email',
            $emailData
        )
            ->assertUnprocessable()
            ->assertExactJson(
                [
                    'message' => 'The given data was invalid',
                    'errors' => [
                        'to' => [
                            [
                                'geselecteerde waarde is ongeldig',
                            ],
                        ],
                        'cc' => [
                            [
                                'geselecteerde waarde is ongeldig',
                            ],
                        ],
                        'bcc' => [
                            [
                                'geselecteerde waarde is ongeldig',
                            ],
                        ],
                    ],
                ]
            );
    }
}
