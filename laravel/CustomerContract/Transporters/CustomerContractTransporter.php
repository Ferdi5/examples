<?php

declare(strict_types=1);

namespace App\Domains\CustomerContract\Transporters;

final class CustomerContractTransporter
{
    private string $salesStatusId;
    private ?int $contractId;
    private int $userId;
    private ?int $operatingCompanyId;
    private ?int $funderId;
    private ?int $compartmentId;
    private ?string $signLocation;
    private ?string $signDate;
    private ?bool $signed;
    private ?array $annexes;
    private ?array $companies;
    private string $page;

    public function getSalesStatusId(): string
    {
        return $this->salesStatusId;
    }

    public function setSalesStatusId(string $salesStatusId): CustomerContractTransporter
    {
        $this->salesStatusId = $salesStatusId;

        return $this;
    }

    public function getContractId(): ?int
    {
        return $this->contractId;
    }

    public function setContractId(?int $contractId): CustomerContractTransporter
    {
        $this->contractId = $contractId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): CustomerContractTransporter
    {
        $this->userId = $userId;

        return $this;
    }

    public function getOperatingCompanyId(): ?int
    {
        return $this->operatingCompanyId;
    }

    public function setOperatingCompanyId(?int $operatingCompanyId): CustomerContractTransporter
    {
        $this->operatingCompanyId = $operatingCompanyId;

        return $this;
    }

    public function getFunderId(): ?int
    {
        return $this->funderId;
    }

    public function setFunderId(?int $funderId): CustomerContractTransporter
    {
        $this->funderId = $funderId;

        return $this;
    }

    public function getCompartmentId(): ?int
    {
        return $this->compartmentId;
    }

    public function setCompartmentId(?int $compartmentId): CustomerContractTransporter
    {
        $this->compartmentId = $compartmentId;

        return $this;
    }

    public function getSignLocation(): ?string
    {
        return $this->signLocation;
    }

    public function setSignLocation(?string $signLocation): CustomerContractTransporter
    {
        $this->signLocation = $signLocation;

        return $this;
    }

    public function getSignDate(): ?string
    {
        return $this->signDate;
    }

    public function setSignDate(?string $signDate): CustomerContractTransporter
    {
        $this->signDate = $signDate;

        return $this;
    }

    public function getSigned(): ?bool
    {
        return $this->signed;
    }

    public function setSigned(?bool $signed): CustomerContractTransporter
    {
        $this->signed = $signed;

        return $this;
    }

    public function getAnnexes(): ?array
    {
        return $this->annexes;
    }

    public function setAnnexes(?array $annexes): CustomerContractTransporter
    {
        $this->annexes = $annexes;

        return $this;
    }

    public function getCompanies(): ?array
    {
        return $this->companies;
    }

    public function setCompanies(?array $companies): CustomerContractTransporter
    {
        $this->companies = $companies;

        return $this;
    }

    public function getPage(): string
    {
        return $this->page;
    }

    public function setPage(string $page): CustomerContractTransporter
    {
        $this->page = $page;

        return $this;
    }
}
