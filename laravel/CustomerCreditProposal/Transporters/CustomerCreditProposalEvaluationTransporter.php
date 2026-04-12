<?php

declare(strict_types=1);

namespace App\Domains\CustomerCreditProposal\Transporters;

final class CustomerCreditProposalEvaluationTransporter
{
    private int $id;
    private string $salesStatusId;
    private int|string $creditProposalId;
    private ?int $creditProposalEvaluationId;
    private int $userId;
    private int $currentUserId;
    private string $currentUserEmail;
    private string $status;
    private ?string $message;
    private array $evaluations;
    private ?string $revisionData;
    private ?string $emailDescription;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): CustomerCreditProposalEvaluationTransporter
    {
        $this->id = $id;

        return $this;
    }

    public function getSalesStatusId(): string
    {
        return $this->salesStatusId;
    }

    public function setSalesStatusId(string $salesStatusId): CustomerCreditProposalEvaluationTransporter
    {
        $this->salesStatusId = $salesStatusId;

        return $this;
    }

    public function getCreditProposalId(): int|string
    {
        return $this->creditProposalId;
    }

    public function setCreditProposalId($creditProposalId): CustomerCreditProposalEvaluationTransporter
    {
        $this->creditProposalId = $creditProposalId;

        return $this;
    }

    public function getCreditProposalEvaluationId(): int
    {
        return $this->creditProposalEvaluationId;
    }

    public function setCreditProposalEvaluationId(int $creditProposalEvaluationId
    ): CustomerCreditProposalEvaluationTransporter {
        $this->creditProposalEvaluationId = $creditProposalEvaluationId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): CustomerCreditProposalEvaluationTransporter
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCurrentUserId(): int
    {
        return $this->currentUserId;
    }

    public function setCurrentUserId(int $currentUserId): CustomerCreditProposalEvaluationTransporter
    {
        $this->currentUserId = $currentUserId;

        return $this;
    }

    public function getCurrentUserEmail(): string
    {
        return $this->currentUserEmail;
    }

    public function setCurrentUserEmail(string $currentUserEmail): CustomerCreditProposalEvaluationTransporter
    {
        $this->currentUserEmail = $currentUserEmail;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): CustomerCreditProposalEvaluationTransporter
    {
        $this->status = $status;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): CustomerCreditProposalEvaluationTransporter
    {
        $this->message = $message;

        return $this;
    }

    public function getEvaluations(): array
    {
        return $this->evaluations;
    }

    public function setEvaluations(array $evaluations): CustomerCreditProposalEvaluationTransporter
    {
        $this->evaluations = $evaluations;

        return $this;
    }

    public function getRevisionData(): ?string
    {
        return $this->revisionData;
    }

    public function setRevisionData(?string $revisionData): CustomerCreditProposalEvaluationTransporter
    {
        $this->revisionData = $revisionData;

        return $this;
    }

    public function getEmailDescription(): ?string
    {
        return $this->emailDescription;
    }

    public function setEmailDescription(?string $emailDescription): CustomerCreditProposalEvaluationTransporter
    {
        $this->emailDescription = $emailDescription;

        return $this;
    }
}
