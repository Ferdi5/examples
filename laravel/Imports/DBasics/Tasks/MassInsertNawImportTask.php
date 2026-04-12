<?php

declare(strict_types=1);

namespace App\Domains\Imports\DBasics\Tasks;

use Throwable;

class MassInsertNawImportTask
{
    /**
     * @param array<string|null> $companies
     * @param array<string|null> $addresses
     * @param array<string|int|null> $debtors
     * @param array<string|null> $persons
     *
     * @throws Throwable
     */
    public function render(
        array $companies,
        array $addresses,
        array $debtors,
        array $persons,
    ): void {
        DB::transaction(
            function () use (
                $addresses,
                $companies,
                $debtors,
                $persons,
            ): void {
                if (!count($companies)) {
                    return;
                }

                if (count($addresses)) {
                    Address::upsert(
                        $addresses,
                        ['address'],
                        [
                            'address',
                            'zip_code',
                            'city',
                            'country',
                        ]
                    );
                    $companies = $this->setCompanyAddressId($companies, $addresses);
                }

                Company::upsert(
                    $companies,
                    ['name'],
                    [
                        'address_id',
                        'name',
                        'business_registration_number',
                        'vat_number',
                        'general_phone_number',
                        'general_mobile_phone',
                        'general_email',
                    ]
                );

                $companies = $this->getCompanies($companies, $persons);
                $debtors = $this->setDebtorCompanyId($companies, $debtors);

                Debtor::upsert(
                    $debtors,
                    ['debtor_code'],
                    [
                        'administration_id',
                        'company_id',
                        'debtor_code',
                    ]
                );

                $companies->load('debtors:id,company_id');

                $companies = $companies->whereIn(
                    'name',
                    collect($persons)
                        ->pluck('company_name')
                )
                    ->values();

                $existingCompanyContactPersons = $this->getExistingCompanyContactPersons(
                    $companies
                );

                [$personsToCreate, $personsToUpdate] = $this->getPersonsToCreateAndUpdate(
                    $persons,
                    $existingCompanyContactPersons
                );

                Person::upsert(
                    $personsToUpdate,
                    ['id'],
                    ['first_name', 'last_name', 'job_title']
                );

                Person::insert(
                    collect($personsToCreate)
                        ->map(fn($person) => [
                            'first_name' => $person['first_name'],
                            'last_name' => $person['last_name'],
                            'job_title' => $person['job_title'],
                            'import_uuid' => $person['import_uuid'],
                        ])
                        ->toArray()
                );

                [$companyContacts, $debtorContacts] = $this->getCompanyAndDebtorContacts(
                    $personsToCreate,
                    $companies
                );

                CompanyContact::upsert(
                    $companyContacts,
                    ['company_id', 'person_id'],
                    ['company_id', 'person_id']
                );
                DebtorContact::upsert(
                    $debtorContacts,
                    ['debtor_id', 'person_id'],
                    ['debtor_id', 'person_id']
                );
            }
        );
    }

    /**
     * @param array<string|null> $companies
     * @param array<string|null> $addresses
     */
    private function setCompanyAddressId(array $companies, array $addresses): array
    {
        $addressAddresses = collect($addresses)->pluck('address');
        $addressAddresses = Address::whereIn('address', $addressAddresses)
            ->pluck('id', 'address');

        foreach ($companies as $key => $company) {
            $companies[$key]['address_id'] = $addressAddresses[$company['address_id']];
        }

        return $companies;
    }

    /**
     * @param array<string|null> $companies
     * @param array<string|null> $persons
     */
    private function getCompanies(array $companies, array $persons): Collection
    {
        $companyNamesFromCompanies = collect($companies)->pluck('name');
        $companyNamesFromPersons = collect($persons)->pluck('company_name');

        $mergedCompanyNames = $companyNamesFromCompanies
            ->concat($companyNamesFromPersons->toArray())
            ->unique()
            ->values();

        return Company::whereIn('name', $mergedCompanyNames)
            ->with([
                'contacts:id,company_id,person_id',
                'contacts.person:id,first_name,last_name,job_title',
                'debtors:id,company_id',
            ])
            ->get(['id', 'name'])
            ->keyBy('name');
    }

    /**
     * @param array<string|int> $debtors
     */
    private function setDebtorCompanyId(Collection $companies, array $debtors): array
    {
        $companyNames = collect($companies)->pluck('id', 'name');

        foreach ($debtors as $key => $debtor) {
            $debtors[$key]['company_id'] = $companyNames[$debtor['company_name']];
            unset($debtors[$key]['company_name']);
        }

        return $debtors;
    }

    private function getExistingCompanyContactPersons(Collection $companies): array
    {
        $existingCompanyContactPersons = [];

        foreach ($companies as $company) {
            foreach ($company->contacts as $contact) {
                $person = $contact->person;

                if (!$person) {
                    continue;
                }

                $key = strtolower(
                    $company->name . '|' .
                    $person->first_name . '|' .
                    $person->last_name
                );

                $existingCompanyContactPersons[$key] = $person->id;
            }
        }

        return $existingCompanyContactPersons;
    }

    /**
     * @param array<string|null> $persons
     * @param array<string|int>  $existingCompanyContactPersons
     */
    private function getPersonsToCreateAndUpdate(
        array $persons,
        array $existingCompanyContactPersons
    ): array {
        $personsToUpdate = [];
        $personsToCreate = [];

        foreach ($persons as $person) {
            $key = strtolower(
                $person['company_name'] . '|' .
                $person['first_name'] . '|' .
                $person['last_name']
            );

            if (isset($existingCompanyContactPersons[$key])) {
                $personsToUpdate[] = [
                    'id' => $existingCompanyContactPersons[$key],
                    'first_name' => $person['first_name'],
                    'last_name' => $person['last_name'],
                    'job_title' => $person['job_title'],
                ];
            } else {
                $personsToCreate[] = [
                    'company_name' => $person['company_name'],
                    'import_uuid' => (string) Str::uuid(),
                    'first_name' => $person['first_name'],
                    'last_name' => $person['last_name'],
                    'job_title' => $person['job_title'],
                ];
            }
        }

        return [$personsToCreate, $personsToUpdate];
    }

    /**
     * @param array<string|null> $personsToCreate
     */
    private function getCompanyAndDebtorContacts(
        array $personsToCreate,
        Collection $companies
    ): array {
        $insertedPersons = Person::whereIn(
            'import_uuid',
            collect($personsToCreate)->pluck('import_uuid')
        )
            ->get(['id', 'import_uuid']);

        $companyContacts = [];
        $debtorContacts = [];
        $companiesByName = $companies->keyBy('name');
        $insertedPersonsByUuid = $insertedPersons->keyBy('import_uuid');
        $debtorsByCompanyId = $companies->flatMap->debtors->keyBy('company_id');

        foreach ($personsToCreate as $person) {
            $company = $companiesByName[$person['company_name']] ?? null;
            $insertedPerson = $insertedPersonsByUuid[$person['import_uuid']] ?? null;
            $insertedDebtor = $company
                ? ($debtorsByCompanyId[$company->id] ?? null)
                : null;

            if ($company?->id && $insertedPerson?->id) {
                $companyContacts[] = [
                    'company_id' => $company->id,
                    'person_id' => $insertedPerson->id,
                ];
            }

            if ($insertedDebtor?->id && $insertedPerson?->id) {
                $debtorContacts[] = [
                    'debtor_id' => $insertedDebtor->id,
                    'person_id' => $insertedPerson->id,
                ];
            }
        }

        return [$companyContacts, $debtorContacts];
    }
}
