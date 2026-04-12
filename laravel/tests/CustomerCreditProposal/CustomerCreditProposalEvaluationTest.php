<?php

declare(strict_types=1);

namespace tests\CustomerCreditProposal;

use App\Domains\CustomerCreditProposal\Models\CustomerCreditProposalEvaluation;
use Tests\CustomerCreditProposal;
use Tests\TestCase;

final class CustomerCreditProposalEvaluationTest extends TestCase
{
    private object $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
    }

    public function test_submitting_unauthenticated(): void
    {
        $this->putJson('/api/v1/sales_statuses/1/credit_proposal/1/submit_evaluations')->assertUnauthorized();
    }

    public function test_submitting_with_all_approved_evaluations_without_preview_data(): void
    {
        $dateTime = CarbonImmutable::make('2023-07-09 09:45:12');
        Carbon::setTestNow($dateTime);

        $this->actingAs($this->user);
        Storage::fake('local');
        $salesStatus = $this->createCustomer();
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        CustomerCreditProposal::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
            ],
        );
        CustomerCreditProposalEvaluation::query()->insert([
            [
                'id' => 1,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user2->id,
                'status' => 'APPROVED',
                'message' => 'yes',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 2,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user1->id,
                'status' => 'APPROVED',
                'message' => 'good one',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 3,
                'customer_credit_proposal_id' => 1,
                'user_id' => $this->user->id,
                'status' => 'APPROVED',
                'message' => null,
                'owner' => 1,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
        ]);
        CustomerCreditProposal::query()->insert(
            [
                'id' => 2,
                'sales_status_id' => $salesStatus->id,
            ],
        );
        CustomerCreditProposalEvaluation::query()->insert([
            [
                'id' => 4,
                'customer_credit_proposal_id' => 2,
                'user_id' => $user2->id,
                'status' => 'CONDITIONAL',
                'message' => 'not sure jet',
                'owner' => 1,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 5,
                'customer_credit_proposal_id' => 2,
                'user_id' => $user1->id,
                'status' => 'CONDITIONAL',
                'message' => 'maybe',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 6,
                'customer_credit_proposal_id' => 2,
                'user_id' => $this->user->id,
                'status' => 'APPROVED',
                'message' => null,
                'owner' => 1,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
        ]);
        $revisionData = json_encode(
            [
                'id' => 1,
                'name' => 'Hoenk',
                'company' => [
                    'id' => 1,
                    'name' => 'Vis',
                ],
            ]
        );

        Http::fake([
            config('impact-factoring-documents-client.base_uri') . '/api/v1/documents' => function (Request $request) {
                self::assertSame('POST', $request->method());

                return Http::response([
                    'id' => 1,
                    'original_filename' => 'credit-proposal-IF-2025-0001-0001.pdf',
                    'hashed_filename' => 'aszrsrzh6srthgdnrsthsbzaewrhtr.pdf',
                    'path' => 'test/aszrsrzh6srthgdnrsthsbzaewrhtr.pdf',
                    'file_size' => 5436,
                    'mime_type' => 'application/pdf',
                ]);
            },
        ]);

        $response = $this->putJson(
            '/api/v1/sales_statuses/' . $salesStatus->id . '/credit_proposal/1/submit_evaluations',
            ['revision_data' => $revisionData]
        );

        $responseData = json_decode($response->getContent());

        $response
            ->assertOk()
            ->assertJsonCount(3, 'sales_status.customer_log')
            ->assertJson(
                [
                    'data' => [
                        'id' => 1,
                        'sales_status_id' => $salesStatus->id,
                        'status' => 'APPROVED',
                    ],
                    'sales_status' => [
                        'id' => $salesStatus->id,
                        'customer_status_id' => 1,
                        'currency' => 'EUR',
                        'customer_log' => [
                            [
                                'sales_status_id' => $salesStatus->id,
                                'user_id' => $this->user->id,
                                'action' => 'EVENT',
                                'title' => 'Kredietvoorstel document is aangemaakt',
                                'message' => 'Kredietvoorstel document credit-proposal-IF-2025-0001-0001.pdf is aangemaakt',
                                'created_at' => '2023-07-09T09:45:12.000000Z',
                                'updated_at' => '2023-07-09T09:45:12.000000Z',
                            ],
                            [
                                'sales_status_id' => $salesStatus->id,
                                'user_id' => $this->user->id,
                                'action' => 'EVENT',
                                'title' => 'Kredietvoorstel beoordelingen zijn definitief gemaakt',
                                'message' => null,
                                'created_at' => '2023-07-09T09:45:12.000000Z',
                                'updated_at' => '2023-07-09T09:45:12.000000Z',
                            ],
                            [
                                'sales_status_id' => $salesStatus->id,
                                'user_id' => $this->user->id,
                                'action' => 'EVENT',
                                'title' => 'Het kredietvoorstel is door de commissie geaccepteerd',
                                'message' => null,
                                'created_at' => '2023-07-09T09:45:12.000000Z',
                                'updated_at' => '2023-07-09T09:45:12.000000Z',
                            ],
                        ],
                    ],
                    'file' => [
                        'id' => $responseData->file->id,
                        'user_id' => $this->user->id,
                        'category' => 'CREDIT_PROPOSAL',
                        'file_name' => 'credit-proposal-IF-2025-0001-0001.pdf',
                        'hash_name' => 'aszrsrzh6srthgdnrsthsbzaewrhtr.pdf',
                        'size' => 5436,
                        'path' => 'test/aszrsrzh6srthgdnrsthsbzaewrhtr.pdf',
                        'url' => '/aszrsrzh6srthgdnrsthsbzaewrhtr.pdf',
                        'mime_type' => 'application/pdf',
                        'locked' => true,
                        'file_id' => $responseData->file->file_id,
                        'file_type' => 'customer_documents',
                        'created_at' => '2023-07-09T09:45:12.000000Z',
                        'updated_at' => '2023-07-09T09:45:12.000000Z',
                    ],
                ]
            );

        $this->assertEmpty(Storage::disk('local')->allFiles());

        $this->assertDatabaseHas('files', [
            'id' => $responseData->file->id,
            'documents_id' => 1,
            'user_id' => $this->user->id,
            'file_type' => 'customer_documents',
            'file_id' => $responseData->file->file_id,
            'category' => 'CREDIT_PROPOSAL',
            'virtual_path' => 'documents/prospects/' . $salesStatus->id . '/credit_proposal',
            'locked' => true,
            'created_at' => '2023-07-09T09:45:12.000000Z',
            'updated_at' => '2023-07-09T09:45:12.000000Z',
        ]);
        $this->assertDatabaseHas(
            'customer_credit_proposal',
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
                'status' => 'APPROVED',
                'updated_at' => '2023-07-09T09:45:12.000000Z',
            ]
        );
        $this->assertDatabaseHas(
            'customer_credit_proposal',
            [
                'id' => 2,
                'sales_status_id' => $salesStatus->id,
                'status' => null,
                'updated_at' => null,
            ]
        );
        $this->assertDatabaseHas('revision', [
            'revision_type' => 'customer_credit_proposal_evaluation',
            'sales_status_id' => $salesStatus->id,
            'user_id' => $this->user->id,
            'version' => config('database.version'),
            'data' => $this->castAsJson(
                [
                    'id' => 1,
                    'name' => 'Hoenk',
                    'company' => [
                        'id' => 1,
                        'name' => 'Vis',
                    ],
                ]
            ),
            'created_at' => '2023-07-09 09:45:12',
            'updated_at' => '2023-07-09 09:45:12',
        ]);
        $this->assertDatabaseHas('customer_log', [
            'sales_status_id' => $salesStatus->id,
            'user_id' => $this->user->id,
            'action' => 'EVENT',
            'title' => 'Kredietvoorstel document is aangemaakt',
            'message' => 'Kredietvoorstel document credit-proposal-IF-2025-0001-0001.pdf is aangemaakt',
            'created_at' => '2023-07-09T09:45:12.000000Z',
            'updated_at' => '2023-07-09T09:45:12.000000Z',
        ]);
        $this->assertDatabaseHas('customer_log', [
            'sales_status_id' => $salesStatus->id,
            'user_id' => $this->user->id,
            'action' => 'EVENT',
            'title' => 'Kredietvoorstel beoordelingen zijn definitief gemaakt',
            'message' => null,
            'created_at' => '2023-07-09T09:45:12.000000Z',
            'updated_at' => '2023-07-09T09:45:12.000000Z',
        ]);
        $this->assertDatabaseHas('customer_log', [
            'sales_status_id' => $salesStatus->id,
            'user_id' => $this->user->id,
            'action' => 'EVENT',
            'title' => 'Het kredietvoorstel is door de commissie geaccepteerd',
            'message' => null,
            'created_at' => '2023-07-09T09:45:12.000000Z',
            'updated_at' => '2023-07-09T09:45:12.000000Z',
        ]);
        $this->assertDatabaseCount('customer_credit_proposal', 2);
        $this->assertDatabaseCount('customer_log', 3);
        $this->assertDatabaseCount('files', 1);
        $this->assertDatabaseCount('revision', 1);
    }

    public function test_submitting_with_all_approved_evaluations_without_revision_data(): void
    {
        $dateTime = CarbonImmutable::make('2023-07-09 09:45:12');
        Carbon::setTestNow($dateTime);

        $this->actingAs($this->user);
        $salesStatus = $this->createCustomer();
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        CustomerCreditProposal::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
            ],
        );
        CustomerCreditProposalEvaluation::query()->insert([
            [
                'id' => 1,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user2->id,
                'status' => 'APPROVED',
                'message' => 'yes',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 2,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user1->id,
                'status' => 'APPROVED',
                'message' => 'good one',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 3,
                'customer_credit_proposal_id' => 1,
                'user_id' => $this->user->id,
                'status' => 'APPROVED',
                'message' => null,
                'owner' => 1,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
        ]);

        $this->putJson('/api/v1/sales_statuses/' . $salesStatus->id . '/credit_proposal/1/submit_evaluations')
            ->assertUnprocessable()
            ->assertExactJson(
                [
                    'message' => 'The given data was invalid',
                    'errors' => [
                        'revision_data' => [
                            'veld is verplicht',
                        ],
                    ],
                ]
            );
    }

    public function test_submitting_with_all_approved_evaluations_with_preview_data(): void
    {
        $dateTime = CarbonImmutable::make('2023-07-09 09:45:12');
        Carbon::setTestNow($dateTime);

        $this->actingAs($this->user);
        Storage::fake('local');
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        Address::query()->insert(
            [
                'id' => 1,
                'address' => 'Straathoofd 58',
                'postal_code' => '1337 GG',
                'city' => 'Houten',
                'country_id' => 1,
            ]
        );
        LegalForm::query()->insert(
            [
                'id' => 1,
                'name' => 'B.V.',
            ]
        );
        $salesStatus = SalesStatus::query()->create(
            [
                'currency' => 'EUR',
                'correspondence_language' => 'nl_NL',
                'customer_status_id' => 1,
            ]
        );
        Company::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
                'address_id' => 1,
                'legal_form_id' => 1,
                'type' => 'PROSPECT',
                'name' => 'Impact Factoring',
                'company_registration_number' => '543543234',
                'vat_number' => 'NL45378435',
                'registered_office' => 'Amsterdam',
                'phone' => '0689863275',
                'website' => 'https://impactfactoring.nl',
            ]
        );
        Person::query()->insert(
            [
                'id' => 1,
                'salutation' => 'MR',
                'initials' => 'G.H.',
                'first_name' => 'Koos',
                'last_name' => 'Haas',
            ]
        );
        $companyPersonContact = CompanyPersonContact::query()->create(
            [
                'company_id' => 1,
                'person_id' => 1,
                'job_title' => 'DIRECTOR_SHAREHOLDER',
            ]
        );
        ContactDetail::query()->insert(
            [
                'id' => 1,
                'contactable_id' => $companyPersonContact->id,
                'contactable_type' => 'company_person_contacts',
                'email_address' => 'harald@beuk.nl',
                'phone_primair' => '0652845678',
            ]
        );
        CompanyPersonCompetence::query()->insert(
            [
                'id' => 1,
                'company_id' => 1,
                'person_id' => 1,
                'competence' => 'independent_sign',
            ]
        );
        $companyPersonContact->contactTypes()->attach(
            [
                1 => [
                    'contact_type_id' => 1,
                ],
            ]
        );
        CustomerData::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
                'lead_channel' => 'WORTH_OF_MOUTH_ADVERTISING',
                'credit_need_amount' => 867856767,
                'annual_revenue_amount' => 55645476,
                'forecast_annual_revenue_amount' => 165345600,
                'debtor_balance_amount' => 4565467800,
                'debtors_count' => 38,
                'invoices_count' => 238,
                'average_invoice_amount' => 65400,
                'default_payment_term_days' => '45 ~ 59',
                'average_payment_term_days' => '60 ~ 90',
            ]
        );
        FinancialFacility::query()->insert(
            [
                'id' => 1,
                'name' => 'krediet.nl',
            ]
        );
        FinancialFacility::query()->insert(
            [
                'id' => 2,
                'name' => 'geldlenen.nl',
            ]
        );
        CustomerDataFinancialFacilitiesRel::query()->insert([
            [
                'id' => 1,
                'customer_data_id' => 1,
                'financial_facility_id' => 1,
                'facility_amount' => 54432400,
                'facility_types' => json_encode([
                    'LOAN',
                    'OG_LOAN',
                ]),
            ],
            [
                'id' => 2,
                'customer_data_id' => 1,
                'financial_facility_id' => 2,
                'facility_amount' => 654455600,
                'facility_types' => json_encode([
                    'LEASE',
                    'FACTORING',
                    'OG_LOAN',
                ]),
            ],
            [
                'id' => 3,
                'customer_data_id' => 1,
                'financial_facility_id' => 1,
                'facility_amount' => 766785400,
                'facility_types' => json_encode([
                    'FACTORING',
                    'WORKING_CAPITAL',
                ]),
            ],
        ]);
        CustomerAnnualFigures::query()->insert(
            [
                [
                    'id' => 1,
                    'sales_status_id' => $salesStatus->id,
                    'year' => '2021A',
                    'revenue_amount' => 5656700,
                    'gross_profit_amount' => 65775600,
                    'gross_margin_percentage' => 12,
                    'opex_amount' => 34500,
                    'ebitda_amount' => 45656456423,
                    'net_profit_amount' => 56454604,
                ],
                [
                    'id' => 2,
                    'sales_status_id' => $salesStatus->id,
                    'year' => '2022A',
                    'revenue_amount' => 67754700,
                    'gross_profit_amount' => 6355376500,
                    'gross_margin_percentage' => 94,
                    'opex_amount' => 5456400,
                    'ebitda_amount' => 45400,
                    'net_profit_amount' => 34435200,
                ],
                [
                    'id' => 3,
                    'sales_status_id' => $salesStatus->id,
                    'year' => '2023P',
                    'revenue_amount' => 8645600,
                    'gross_profit_amount' => 76576500,
                    'gross_margin_percentage' => 9,
                    'opex_amount' => 34500,
                    'ebitda_amount' => 455400,
                    'net_profit_amount' => 7665700,
                ],
            ]
        );
        CustomerDebtorAnalyse::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
                'total_debtors_amount' => 240000000,
                'total_amount' => 212358755,
                'total_limit_amount' => 215358755,
                'total_non_financiable_amount' => 0,
                'total_financiable_amount' => 215358755,
                'total_balance_percentage' => 88,
            ],
        );
        CustomerDebtorAnalyseRule::query()->insert(
            [
                [
                    'id' => 1,
                    'customer_debtor_analyses_id' => 1,
                    'debtor_name' => 'Jaap en zonen',
                    'company_registration_number' => '5436754345',
                    'country_id' => 5,
                    'total_balance_percentage' => 14,
                    'total_amount' => 34567845,
                    'non_financiable_amount' => 0,
                    'limit_amount' => 35567845,
                    'financiable_amount' => 35567845,
                    'memo' => 'Is goeie debiteur',
                    'insurer_id' => 2,
                    'creditsafe_score' => 4,
                    'grade_score' => 4,
                ],
                [
                    'id' => 2,
                    'customer_debtor_analyses_id' => 1,
                    'debtor_name' => 'Harry Hansen',
                    'company_registration_number' => '765654345',
                    'country_id' => 17,
                    'total_balance_percentage' => 10,
                    'limit_amount' => 24456400,
                    'non_financiable_amount' => 0,
                    'financiable_amount' => 24456400,
                    'total_amount' => 23456400,
                    'memo' => 'Veel geld',
                    'insurer_id' => 4,
                    'creditsafe_score' => 5,
                    'grade_score' => 4,
                ],
                [
                    'id' => 3,
                    'customer_debtor_analyses_id' => 1,
                    'debtor_name' => 'Hoenk Befroe',
                    'company_registration_number' => 'NL2342345436',
                    'country_id' => 24,
                    'total_balance_percentage' => 64,
                    'limit_amount' => 155334510,
                    'non_financiable_amount' => 0,
                    'financiable_amount' => 155334510,
                    'total_amount' => 154334510,
                    'memo' => 'Is goed voor ons',
                    'insurer_id' => 1,
                    'creditsafe_score' => 4,
                    'grade_score' => 5,
                ],
            ]
        );
        CustomerCreditorAnalyses::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
                'total_amount' => 8800,
                'total_creditors_amount' => 10445,
            ],
        );
        CustomerCreditorAnalyse::query()->insert(
            [
                [
                    'id' => 1,
                    'customer_creditor_analyses_id' => 1,
                    'company_registration_number' => '34232454',
                    'memo' => 'Grote Traktor',
                    'name' => 'Hoenk',
                    'nature_of_product' => 'Bouwkunde',
                    'total_amount' => 3400,
                ],
                [
                    'id' => 2,
                    'customer_creditor_analyses_id' => 1,
                    'company_registration_number' => '4332454',
                    'memo' => 'Dakpannen en Goot',
                    'name' => 'Harres',
                    'nature_of_product' => 'Maatwerk op dak',
                    'total_amount' => 5400,
                ],
            ]
        );
        CustomerCreditProposal::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
                'person_id' => 1,
                'status' => 'EVALUATION',
                'limit_costs' => 'DISCOUNT',
                'limit_costs_description' => 'Test limit costs description message',
                'deviating_provisions' => json_encode(
                    [
                        [
                            'deviating_key' => 'maximum_concentration_percentage',
                            'deviating_debtors' => [6, 5],
                            'deviating_percentage' => '54',
                        ],
                        [
                            'deviating_key' => 'maximum_concentration_percentage',
                            'deviating_debtors' => [5],
                            'deviating_percentage' => '12',
                        ],
                    ],
                ),
                'seasonal_influences' => 'YES',
                'seasonal_influences_description' => 'Test seasonal influences description message',
                'billing_interval' => 'WEEKLY',
                'debtors_appear_as_creditors' => 'YES',
                'debtors_appear_as_creditors_description' => 'Test debtors appear as creditors description message',
                'nature_of_billing' => 'ADVANCE_BILLING',
                'bonus_or_payment_discounts' => 'YES',
                'bonus_or_payment_discounts_description' => 'Test bonus or payment discounts description message',
                'credit_insured_debtors' => 'YES',
                'credit_insured_debtors_description' => 'coface',
                'write_offs_debtors_past_three_years' => 'Test write offs debtors past three years message',
                'doubtful_debtors' => 'YES',
                'doubtful_debtors_description' => 'Test doubtful debtors description message',
                'general_delivery_and_payment_conditions' => 'Test general delivery and payment conditions message',
                'portfolio_concentrations' => 'YES',
                'portfolio_concentrations_description' => 'Test portfolio concentrations description message',
                'order_to_cash_process' => 'Test order to cash process message',
                'general_description' => 'Test general description message',
                'funding_request_reason' => 'Test funding request reason message',
                'current_funder_description' => 'Test current funder description message',
                'requested_funding_description' => 'Test requested funding description message',
                'financials_description' => 'Test financial description message',
                'background_request_description' => 'Test background request description message',
                'main_risks' => 'Test main risks message',
                'pricing_description' => 'Test pricing description message',
                'decision_description' => 'Test decision description message',
                'actions_to_be_taken' => 'Test actions to be taken message',
            ],
        );
        CustomerCreditProposalEvaluation::query()->insert([
            [
                'id' => 1,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user2->id,
                'status' => 'APPROVED',
                'message' => 'yes',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 2,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user1->id,
                'status' => 'APPROVED',
                'message' => 'good one',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 3,
                'customer_credit_proposal_id' => 1,
                'user_id' => $this->user->id,
                'status' => 'APPROVED',
                'message' => null,
                'owner' => 1,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
        ]);
        TransactionFinancing::query()->insert(
            [
                'id' => 2,
                'maximum_annual_revenue_percentage' => '23.65',
                'maximum_purchase_orders_revenue_percentage' => 87,
                'setup_fee_amount' => 43,
                'maximum_financing_amount' => 765567,
                'monthly_transaction_interest_rate_percentage' => '23.543',
                'maximum_monthly_duration_individual_transaction' => 76,
            ]
        );
        CustomerOfferDetails::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
                'transaction_financing_id' => 2,
                'maximum_financing_amount' => 7865,
                'financing_rate_percentage' => '34.23',
                'factor_commission_percentage' => '78.0030',
                'add_factoring_fee_abroad' => true,
                'factoring_fee_abroad_percentage' => '54.0000',
                'factoring_fee_credit_insurance' => true,
                'raise_advance_interest_rate_percentage' => '65.001',
                'euribor_interest_term_months' => 3,
                'credit_provision_percentage' => '34.00002',
                'setup_fee_amount' => 76547,
                'own_risk_amount' => 87667,
                'minimum_factor_commission_amount' => 87643,
                'duration_months' => 12,
                'notice_period_months' => 6,
                'scoring_chance_percentage' => 45,
            ]
        );
        $revisionData = json_encode(
            [
                'id' => 1,
                'name' => 'Hoenk',
                'company' => [
                    'id' => 1,
                    'name' => 'Vis',
                ],
            ]
        );

        Http::fake([
            config('impact-factoring-documents-client.base_uri') . '/api/v1/documents' => function (Request $request) {
                self::assertSame('POST', $request->method());

                return Http::response([
                    'id' => 1,
                    'original_filename' => 'credit-proposal-IF-2025-0001-0001.pdf',
                    'hashed_filename' => 'aszrsrzh6srthgdnrsthsbzaewrhtr.pdf',
                    'path' => 'test/aszrsrzh6srthgdnrsthsbzaewrhtr.pdf',
                    'file_size' => 5436,
                    'mime_type' => 'application/pdf',
                ]);
            },
        ]);

        $response = $this->putJson(
            '/api/v1/sales_statuses/' . $salesStatus->id . '/credit_proposal/1/submit_evaluations',
            ['revision_data' => $revisionData]
        );

        $responseData = json_decode($response->getContent());

        $response->assertOk()
            ->assertJsonCount(3, 'sales_status.customer_log')
            ->assertJson(
                [
                    'data' => [
                        'id' => 1,
                        'sales_status_id' => $salesStatus->id,
                        'status' => 'APPROVED',
                    ],
                    'sales_status' => [
                        'id' => $salesStatus->id,
                        'customer_status_id' => 1,
                        'currency' => 'EUR',
                        'customer_log' => [
                            [
                                'sales_status_id' => $salesStatus->id,
                                'user_id' => $this->user->id,
                                'action' => 'EVENT',
                                'title' => 'Kredietvoorstel document is aangemaakt',
                                'message' => 'Kredietvoorstel document credit-proposal-IF-2025-0001-0001.pdf is aangemaakt',
                                'created_at' => '2023-07-09T09:45:12.000000Z',
                                'updated_at' => '2023-07-09T09:45:12.000000Z',
                            ],
                            [
                                'sales_status_id' => $salesStatus->id,
                                'user_id' => $this->user->id,
                                'action' => 'EVENT',
                                'title' => 'Kredietvoorstel beoordelingen zijn definitief gemaakt',
                                'message' => null,
                                'created_at' => '2023-07-09T09:45:12.000000Z',
                                'updated_at' => '2023-07-09T09:45:12.000000Z',
                            ],
                            [
                                'sales_status_id' => $salesStatus->id,
                                'user_id' => $this->user->id,
                                'action' => 'EVENT',
                                'title' => 'Het kredietvoorstel is door de commissie geaccepteerd',
                                'message' => null,
                                'created_at' => '2023-07-09T09:45:12.000000Z',
                                'updated_at' => '2023-07-09T09:45:12.000000Z',
                            ],
                        ],
                    ],
                    'file' => [
                        'id' => $responseData->file->id,
                        'user_id' => $this->user->id,
                        'category' => 'CREDIT_PROPOSAL',
                        'file_name' => 'credit-proposal-IF-2025-0001-0001.pdf',
                        'hash_name' => 'aszrsrzh6srthgdnrsthsbzaewrhtr.pdf',
                        'size' => 5436,
                        'path' => 'test/aszrsrzh6srthgdnrsthsbzaewrhtr.pdf',
                        'url' => '/aszrsrzh6srthgdnrsthsbzaewrhtr.pdf',
                        'mime_type' => 'application/pdf',
                        'locked' => true,
                        'file_id' => $responseData->file->file_id,
                        'file_type' => 'customer_documents',
                        'created_at' => '2023-07-09T09:45:12.000000Z',
                        'updated_at' => '2023-07-09T09:45:12.000000Z',
                    ],
                ]
            );

        $this->assertEmpty(Storage::disk('local')->allFiles());

        $this->assertDatabaseHas('files', [
            'id' => $responseData->file->id,
            'documents_id' => 1,
            'user_id' => $this->user->id,
            'file_type' => 'customer_documents',
            'file_id' => $responseData->file->file_id,
            'category' => 'CREDIT_PROPOSAL',
            'virtual_path' => 'documents/prospects/' . $salesStatus->id . '/credit_proposal',
            'locked' => true,
            'created_at' => '2023-07-09T09:45:12.000000Z',
            'updated_at' => '2023-07-09T09:45:12.000000Z',
        ]);
        $this->assertDatabaseHas(
            'customer_credit_proposal',
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
                'status' => 'APPROVED',
                'updated_at' => '2023-07-09T09:45:12.000000Z',
            ]
        );
        $this->assertDatabaseHas('revision', [
            'revision_type' => 'customer_credit_proposal_evaluation',
            'sales_status_id' => $salesStatus->id,
            'user_id' => $this->user->id,
            'version' => config('database.version'),
            'data' => $this->castAsJson(
                [
                    'id' => 1,
                    'name' => 'Hoenk',
                    'company' => [
                        'id' => 1,
                        'name' => 'Vis',
                    ],
                ]
            ),
            'created_at' => '2023-07-09 09:45:12',
            'updated_at' => '2023-07-09 09:45:12',
        ]);
        $this->assertDatabaseHas('customer_log', [
            'sales_status_id' => $salesStatus->id,
            'user_id' => $this->user->id,
            'action' => 'EVENT',
            'title' => 'Kredietvoorstel document is aangemaakt',
            'message' => 'Kredietvoorstel document credit-proposal-IF-2025-0001-0001.pdf is aangemaakt',
            'created_at' => '2023-07-09T09:45:12.000000Z',
            'updated_at' => '2023-07-09T09:45:12.000000Z',
        ]);
        $this->assertDatabaseHas('customer_log', [
            'sales_status_id' => $salesStatus->id,
            'user_id' => $this->user->id,
            'action' => 'EVENT',
            'title' => 'Kredietvoorstel beoordelingen zijn definitief gemaakt',
            'message' => null,
            'created_at' => '2023-07-09T09:45:12.000000Z',
            'updated_at' => '2023-07-09T09:45:12.000000Z',
        ]);
        $this->assertDatabaseHas('customer_log', [
            'sales_status_id' => $salesStatus->id,
            'user_id' => $this->user->id,
            'action' => 'EVENT',
            'title' => 'Het kredietvoorstel is door de commissie geaccepteerd',
            'message' => null,
            'created_at' => '2023-07-09T09:45:12.000000Z',
            'updated_at' => '2023-07-09T09:45:12.000000Z',
        ]);
        $this->assertDatabaseCount('customer_credit_proposal', 1);
        $this->assertDatabaseCount('customer_log', 3);
        $this->assertDatabaseCount('files', 1);
        $this->assertDatabaseCount('revision', 1);
    }

    public function test_submitting_with_one_rejected_evaluation(): void
    {
        $dateTime = CarbonImmutable::make('2023-07-09 09:45:12');
        Carbon::setTestNow($dateTime);

        $this->actingAs($this->user);
        Storage::fake('local');
        $salesStatus = $this->createCustomer();
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        CustomerCreditProposal::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
            ],
        );
        CustomerCreditProposalEvaluation::query()->insert([
            [
                'id' => 1,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user2->id,
                'status' => 'APPROVED',
                'message' => 'yes',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 2,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user1->id,
                'status' => 'REJECTED',
                'message' => 'no',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 3,
                'customer_credit_proposal_id' => 1,
                'user_id' => $this->user->id,
                'status' => 'APPROVED',
                'message' => null,
                'owner' => 1,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
        ]);

        $this->putJson(
            '/api/v1/sales_statuses/' . $salesStatus->id . '/credit_proposal/1/submit_evaluations',
        )
            ->assertOk()
            ->assertJsonCount(2, 'sales_status.customer_log')
            ->assertJson(
                [
                    'data' => [
                        'id' => 1,
                        'sales_status_id' => $salesStatus->id,
                        'status' => 'REJECTED',
                    ],
                    'sales_status' => [
                        'id' => $salesStatus->id,
                        'customer_status_id' => 1,
                        'currency' => 'EUR',
                        'customer_log' => [
                            [
                                'sales_status_id' => $salesStatus->id,
                                'user_id' => $this->user->id,
                                'action' => 'EVENT',
                                'title' => 'Kredietvoorstel beoordelingen zijn definitief gemaakt',
                                'message' => null,
                                'created_at' => '2023-07-09T09:45:12.000000Z',
                                'updated_at' => '2023-07-09T09:45:12.000000Z',
                            ],
                            [
                                'sales_status_id' => $salesStatus->id,
                                'user_id' => $this->user->id,
                                'action' => 'EVENT',
                                'title' => 'Het kredietvoorstel is door de commissie geweigerd',
                                'message' => null,
                                'created_at' => '2023-07-09T09:45:12.000000Z',
                                'updated_at' => '2023-07-09T09:45:12.000000Z',
                            ],
                        ],
                    ],
                    'file' => [],
                ]
            );

        $this->assertEmpty(Storage::disk('local')->allFiles());

        $this->assertDatabaseHas(
            'customer_credit_proposal',
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
                'status' => 'REJECTED',
                'updated_at' => '2023-07-09T09:45:12.000000Z',
            ]
        );
        $this->assertDatabaseHas('customer_log', [
            'sales_status_id' => $salesStatus->id,
            'user_id' => $this->user->id,
            'action' => 'EVENT',
            'title' => 'Kredietvoorstel beoordelingen zijn definitief gemaakt',
            'message' => null,
            'created_at' => '2023-07-09T09:45:12.000000Z',
            'updated_at' => '2023-07-09T09:45:12.000000Z',
        ]);
        $this->assertDatabaseHas('customer_log', [
            'sales_status_id' => $salesStatus->id,
            'user_id' => $this->user->id,
            'action' => 'EVENT',
            'title' => 'Het kredietvoorstel is door de commissie geweigerd',
            'message' => null,
            'created_at' => '2023-07-09T09:45:12.000000Z',
            'updated_at' => '2023-07-09T09:45:12.000000Z',
        ]);
        $this->assertDatabaseCount('customer_credit_proposal', 1);
        $this->assertDatabaseCount('customer_log', 2);
        $this->assertDatabaseCount('revision', 0);
    }

    public function test_submitting_with_one_conditional_evaluation(): void
    {
        $dateTime = CarbonImmutable::make('2023-07-09 09:45:12');
        Carbon::setTestNow($dateTime);

        $this->actingAs($this->user);
        $salesStatus = $this->createCustomer();
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        CustomerCreditProposal::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
            ],
        );
        CustomerCreditProposalEvaluation::query()->insert([
            [
                'id' => 1,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user2->id,
                'status' => 'APPROVED',
                'message' => 'yes',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 2,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user1->id,
                'status' => 'CONDITIONAL',
                'message' => 'maybe',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 3,
                'customer_credit_proposal_id' => 1,
                'user_id' => $this->user->id,
                'status' => 'APPROVED',
                'message' => null,
                'owner' => 1,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
        ]);

        $this->putJson(
            '/api/v1/sales_statuses/' . $salesStatus->id . '/credit_proposal/1/submit_evaluations',
        )
            ->assertUnprocessable()
            ->assertExactJson(
                [
                    'message' => 'Evaluations with status conditional needs to be evaluated (' . GlobalTracer::getId(
                    ) . ')',
                    'errors' => [],
                ]
            );
    }

    public function test_submitting_with_evaluation_without_status(): void
    {
        $dateTime = CarbonImmutable::make('2023-07-09 09:45:12');
        Carbon::setTestNow($dateTime);

        $this->actingAs($this->user);
        $salesStatus = $this->createCustomer();
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        CustomerCreditProposal::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
            ],
        );
        CustomerCreditProposalEvaluation::query()->insert([
            [
                'id' => 1,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user2->id,
                'status' => 'APPROVED',
                'message' => 'yes',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 2,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user1->id,
                'status' => null,
                'message' => null,
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 3,
                'customer_credit_proposal_id' => 1,
                'user_id' => $this->user->id,
                'status' => 'APPROVED',
                'message' => null,
                'owner' => 1,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
        ]);

        $this->putJson(
            '/api/v1/sales_statuses/' . $salesStatus->id . '/credit_proposal/1/submit_evaluations',
        )
            ->assertUnprocessable()
            ->assertExactJson(
                [
                    'message' => 'Not all evaluations are submitted (' . GlobalTracer::getId() . ')',
                    'errors' => [],
                ]
            );
    }

    public function test_submitting_with_non_existing_sales_status_id(): void
    {
        $this->actingAs($this->user);
        $salesStatus = $this->createCustomer();

        CustomerCreditProposal::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
            ],
        );
        CustomerCreditProposalEvaluation::query()->insert(
            [
                'id' => 1,
                'customer_credit_proposal_id' => 1,
            ],
        );

        $this->putJson('/api/v1/sales_statuses/' . $salesStatus->id + 1 . '/credit_proposal/1/submit_evaluations')
            ->assertUnprocessable()
            ->assertExactJson(
                [
                    'message' => 'The given data was invalid',
                    'errors' => [
                        'sales_status_id' => [
                            'geselecteerde waarde is ongeldig',
                        ],
                    ],
                ]
            );
    }

    public function test_submitting_with_non_existing_credit_proposal_id(): void
    {
        $this->actingAs($this->user);
        $salesStatus = $this->createCustomer();

        CustomerCreditProposal::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
            ],
        );

        $this->putJson('/api/v1/sales_statuses/' . $salesStatus->id . '/credit_proposal/2/submit_evaluations')
            ->assertUnprocessable()
            ->assertExactJson(
                [
                    'message' => 'The given data was invalid',
                    'errors' => [
                        'credit_proposal_id' => [
                            'geselecteerde waarde is ongeldig',
                        ],
                        'revision_data' => [
                            'veld is verplicht',
                        ],
                    ],
                ]
            );
    }

    public function test_submitting_without_being_evaluations_owner(): void
    {
        $dateTime = CarbonImmutable::make('2023-07-09 09:45:12');
        Carbon::setTestNow($dateTime);
        $this->actingAs($this->user);
        $salesStatus = $this->createCustomer();
        $user1 = $this->createUser();
        CustomerCreditProposal::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
            ],
        );
        CustomerCreditProposalEvaluation::query()->insert(
            [
                'id' => 1,
                'customer_credit_proposal_id' => 1,
                'user_id' => $this->user->id,
                'status' => 'APPROVED',
                'message' => 'not sure jet',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
        );
        CustomerCreditProposalEvaluation::query()->insert(
            [
                'id' => 2,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user1->id,
                'status' => 'APPROVED',
                'message' => null,
                'owner' => 1,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
        );

        Http::fake([
            config('impact-factoring-documents-client.base_uri') . '/api/v1/documents' => function (Request $request) {
                self::assertSame('POST', $request->method());

                return Http::response([
                    'id' => 1,
                    'original_filename' => 'credit-proposal-IF-2025-0001-0001.pdf',
                    'hashed_filename' => 'aszrsrzh6srthgdnrsthsbzaewrhtr.pdf',
                    'path' => 'test/aszrsrzh6srthgdnrsthsbzaewrhtr.pdf',
                    'file_size' => 5436,
                    'mime_type' => 'application/pdf',
                ]);
            },
        ]);

        $this->putJson(
            '/api/v1/sales_statuses/' . $salesStatus->id . '/credit_proposal/1/submit_evaluations',
            ['revision_data' => '[]']
        )
            ->assertOk();
    }

    public function test_submitting_with_removed_user_evaluation(): void
    {
        $dateTime = CarbonImmutable::make('2023-07-09 09:45:12');
        Carbon::setTestNow($dateTime);

        $this->actingAs($this->user);
        Storage::fake('local');
        $salesStatus = $this->createCustomer();
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        CustomerCreditProposal::query()->insert(
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
            ],
        );
        CustomerCreditProposalEvaluation::query()->insert([
            [
                'id' => 1,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user2->id,
                'status' => 'REJECTED',
                'message' => 'no',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 2,
                'customer_credit_proposal_id' => 1,
                'user_id' => $user1->id,
                'status' => 'REMOVED',
                'message' => 'bye',
                'owner' => 0,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
            [
                'id' => 3,
                'customer_credit_proposal_id' => 1,
                'user_id' => $this->user->id,
                'status' => 'APPROVED',
                'message' => null,
                'owner' => 1,
                'created_at' => '2023-07-09 09:45:12',
                'updated_at' => '2023-07-09 09:45:12',
            ],
        ]);

        $this->putJson(
            '/api/v1/sales_statuses/' . $salesStatus->id . '/credit_proposal/1/submit_evaluations',
        )
            ->assertOk()
            ->assertJsonCount(2, 'sales_status.customer_log')
            ->assertJson(
                [
                    'data' => [
                        'id' => 1,
                        'sales_status_id' => $salesStatus->id,
                        'status' => 'REJECTED',
                    ],
                    'sales_status' => [
                        'id' => $salesStatus->id,
                        'customer_status_id' => 1,
                        'currency' => 'EUR',
                        'customer_log' => [
                            [
                                'sales_status_id' => $salesStatus->id,
                                'user_id' => $this->user->id,
                                'action' => 'EVENT',
                                'title' => 'Kredietvoorstel beoordelingen zijn definitief gemaakt',
                                'message' => null,
                                'created_at' => '2023-07-09T09:45:12.000000Z',
                                'updated_at' => '2023-07-09T09:45:12.000000Z',
                            ],
                            [
                                'sales_status_id' => $salesStatus->id,
                                'user_id' => $this->user->id,
                                'action' => 'EVENT',
                                'title' => 'Het kredietvoorstel is door de commissie geweigerd',
                                'message' => null,
                                'created_at' => '2023-07-09T09:45:12.000000Z',
                                'updated_at' => '2023-07-09T09:45:12.000000Z',
                            ],
                        ],
                    ],
                    'file' => [],
                ]
            );

        $this->assertEmpty(Storage::disk('local')->allFiles());

        $this->assertDatabaseHas(
            'customer_credit_proposal',
            [
                'id' => 1,
                'sales_status_id' => $salesStatus->id,
                'status' => 'REJECTED',
                'updated_at' => '2023-07-09T09:45:12.000000Z',
            ]
        );
        $this->assertDatabaseHas('customer_log', [
            'sales_status_id' => $salesStatus->id,
            'user_id' => $this->user->id,
            'action' => 'EVENT',
            'title' => 'Kredietvoorstel beoordelingen zijn definitief gemaakt',
            'message' => null,
            'created_at' => '2023-07-09T09:45:12.000000Z',
            'updated_at' => '2023-07-09T09:45:12.000000Z',
        ]);
        $this->assertDatabaseHas('customer_log', [
            'sales_status_id' => $salesStatus->id,
            'user_id' => $this->user->id,
            'action' => 'EVENT',
            'title' => 'Het kredietvoorstel is door de commissie geweigerd',
            'message' => null,
            'created_at' => '2023-07-09T09:45:12.000000Z',
            'updated_at' => '2023-07-09T09:45:12.000000Z',
        ]);
        $this->assertDatabaseCount('customer_credit_proposal', 1);
        $this->assertDatabaseCount('customer_log', 2);
    }
}
