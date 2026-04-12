<?php

namespace Tests;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    // use DatabaseTransactions;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function actingAs(Authenticatable $user, $guard = null): TestCase|static
    {
        $userData = $user->toArray();
        $userData['permissions'] = [];
        $userData['role'] = [];

        Auth::login(
            (new UserService)->buildModel(
                $userData,
                User::class
            )
        );

        $this->be($user, $guard);

        return $this;
    }

    public function createUser(): User
    {
        return User::query()->create([
            'accounts_id' => 1,
            'first_name' => 'Hoenk',
            'last_name' => 'Begoerer',
            'email' => Factory::create('EN')->email,
            'auth_token' => 'test_token',
        ]);
    }

    public function createCustomer(): SalesStatus
    {
        $salesStatus = SalesStatus::query()->create(
            [
                'currency' => 'EUR',
                'correspondence_language' => 'nl_NL',
                'customer_status_id' => 1,
            ]
        );
        Company::query()->create(
            [
                'sales_status_id' => $salesStatus->id,
                'type' => 'PROSPECT',
                'name' => Factory::create('EN')->name,
            ]
        );
        CustomerData::query()->create(
            [
                'sales_status_id' => $salesStatus->id,
            ]
        );

        /** @var SalesStatus */
        return $salesStatus;
    }

    public function createContactPerson(): Person
    {
        $person = Person::query()->create(
            [
                'last_name' => Factory::create('EN')->lastName,
            ]
        );

        CompanyPersonContact::query()->create(
            [
                'person_id' => $person->id,
            ]
        );

        /** @var Person */
        return $person;
    }

    public static function invalidDutchPostcodes(): array
    {
        return [
            ['1234AB'],
            ['123 AB'],
            ['12345 AB'],
            ['123A AB'],
            ['1234AA'],
            ['1234 tt'],
            ['abcd EF'],
            ['ABCD 12'],
            ['abcd 56'],
            ['0000 AB'],
            ['1234 A'],
            ['1234 ABC'],
            [' 1234 AB'],
            ['1234 AB '],
        ];
    }
}
