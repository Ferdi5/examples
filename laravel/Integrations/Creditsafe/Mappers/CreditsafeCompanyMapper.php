<?php

declare(strict_types=1);

namespace App\Domains\Integrations\Creditsafe\Mappers;

class CreditsafeCompanyMapper
{
    public function mapCompany(array $company): array
    {
        $branch = Arr::get($company, 'report.companySummary.mainActivity.code', null);

        return [
            'name' => Arr::get($company, 'report.companyIdentification.basicInformation.businessName', null),
            'legalForm' => Arr::get(
                $company,
                'report.companyIdentification.basicInformation.legalForm.description',
                null
            ),
            'registeredOffice' => Arr::get($company, 'report.additionalInformation.misc.statutaireSeal', null),
            'street' => Arr::get($company, 'report.companyIdentification.basicInformation.contactAddress.street', null),
            'houseNumber' => Arr::get(
                $company,
                'report.companyIdentification.basicInformation.contactAddress.houseNumber',
                null
            ),
            'postalCode' => Arr::get(
                $company,
                'report.companyIdentification.basicInformation.contactAddress.postalCode',
                null
            ),
            'city' => Arr::get($company, 'report.companyIdentification.basicInformation.contactAddress.city', null),
            'country' => Arr::get($company, 'report.companyIdentification.basicInformation.country', null),
            'telephone' => Arr::get(
                $company,
                'report.companyIdentification.basicInformation.contactAddress.telephone',
                null
            ),
            'website' => Arr::get($company, 'report.contactInformation.websites.0', null),
            'regNo' => Arr::get(
                $company,
                'report.companyIdentification.basicInformation.companyRegistrationNumber',
                null
            ),
            'vatNo' => Arr::get($company, 'report.companyIdentification.basicInformation.vatRegistrationNumber', null),
            'branch' => $branch ? Arr::get(__('constants/branches'), $branch, null) : null,
            'creditsafeScore' => Arr::get($company, 'report.creditScore.currentCreditRating.providerValue.value', null),
        ];
    }

    public function mapCompanies(array $companies): array
    {
        $mappedCompanies = [];
        foreach ($companies as $company) {
            $mappedCompanies[] = [
                'id' => Arr::get($company, 'id', null),
                'regNo' => $this->formatRegNo($company),
                'vatNo' => $this->formatVatNo($company),
                'status' => Arr::get($company, 'status', null),
                'name' => Arr::get($company, 'tradingNames.0', null),
                'legalForm' => Arr::get($company, 'legalForm', null),
                'country' => Arr::get($company, 'country', null),
                'city' => Arr::get($company, 'address.city', null),
                'postalCode' => Arr::get($company, 'address.postCode', null),
                'street' => Arr::get($company, 'address.street', null),
                'houseNumber' => Arr::get($company, 'address.houseNo', null),
                'dateOfLatestChange' => $this->formatDateOfLatestChange($company),
            ];
        }

        return $mappedCompanies;
    }

    private function formatRegNo($company): ?string
    {
        $regNo = Arr::get($company, 'regNo', null);
        $country = Arr::get($company, 'country', null);

        if ($regNo && $country === 'NL') {
            $regNo = substr($regNo, 0, 8);
        }

        return $regNo;
    }

    private function formatVatNo($company): ?string
    {
        $vatNo = Arr::get($company, 'vatNo', null);
        $country = Arr::get($company, 'country', null);

        if ($vatNo && is_array($vatNo)) {
            $vatNo = $vatNo[0];
        }

        if ($vatNo && $country === 'NL' && !str_starts_with(strtolower($vatNo), 'nl')) {
            $vatNo = 'NL' . $vatNo;
        }

        return $vatNo;
    }

    private function formatDateOfLatestChange($company): ?string
    {
        $dateOfLatestChange = Arr::get($company, 'dateOfLatestChange', null);
        if ($dateOfLatestChange) {
            $dateOfLatestChange = CarbonImmutable::parse($dateOfLatestChange)->format('d-m-y h:i:s');
        }

        return $dateOfLatestChange;
    }
}
