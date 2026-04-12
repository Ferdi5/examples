<?php

declare(strict_types=1);

namespace App\Domains\CustomerCreditProposal\Actions;

use App\Domains\CustomerCreditProposal\Models\CustomerCreditProposalEvaluation;
use App\Domains\CustomerCreditProposal\Repositories\CustomerCreditProposalEvaluationRepository;
use App\Domains\CustomerCreditProposal\Resources\CustomerCreditProposalResource;
use App\Domains\CustomerCreditProposal\Transporters\CustomerCreditProposalEvaluationTransporter;
use Throwable;

final class SubmitCreditProposalEvaluationsAction extends Action
{
    public function __construct(
        private readonly CustomerCreditProposalEvaluationRepository $creditProposalEvaluationRepository,
        private readonly SalesStatusRepository $salesStatusRepository,
        private readonly ListOfferCertaintiesAction $listOfferCertaintiesAction,
        private readonly GeneratePdfAction $generatePdfAction
    ) {
    }

    /**
     * @throws Throwable
     */
    public function run(
        CustomerCreditProposalEvaluationTransporter $creditProposalEvaluationTransporter
    ): CustomerCreditProposalResource {
        return DB::transaction(function () use ($creditProposalEvaluationTransporter) {
            $evaluations = $this->creditProposalEvaluationRepository->fetchOpenEvaluations(
                $creditProposalEvaluationTransporter->getCreditProposalId()
            );

            if (!count($evaluations)) {
                throw new ApplicationException('No active evaluations found');
            }

            if ($evaluations->firstWhere('status', null)) {
                throw new ApplicationException('Not all evaluations are submitted');
            }

            if ($evaluations->firstWhere(
                'status',
                CustomerCreditProposalEvaluation::STATUS_CONDITIONAL
            )) {
                throw new ApplicationException(
                    'Evaluations with status conditional needs to be evaluated'
                );
            }

            $status = $this->getCreditProposalStatus($evaluations);

            if (!$status) {
                throw new ApplicationException('Unable to compute a credit proposal status');
            }

            $creditProposal = CustomerCreditProposal
                ::query()
                ->where('id', $creditProposalEvaluationTransporter->getCreditProposalId())
                ->where('sales_status_id', $creditProposalEvaluationTransporter->getSalesStatusId())
                ->first();

            if (!$creditProposal) {
                throw (new ModelNotFoundException)->setModel(
                    CustomerCreditProposal::class,
                    $creditProposalEvaluationTransporter->getCreditProposalId()
                );
            }

            $creditProposal->update([
                'status' => $status,
            ]);

            $file = null;
            if ($status === CustomerCreditProposal::STATUS_APPROVED) {
                $action = new StoreCreditProposalAsDocumentFileAction(
                    $this->salesStatusRepository,
                    $this->listOfferCertaintiesAction,
                    $this->generatePdfAction
                );
                $file = $action->run(
                    (new CustomerCreditProposalTransporter)
                        ->setSalesStatusId($creditProposalEvaluationTransporter->getSalesStatusId())
                        ->setUserId($creditProposalEvaluationTransporter->getCurrentUserId())
                );

                $currentUserEvaluation = $evaluations->firstWhere(
                    'user_id',
                    $creditProposalEvaluationTransporter->getCurrentUserId()
                );

                (new StoreRevisionAction)->run(
                    $currentUserEvaluation,
                    $creditProposalEvaluationTransporter->getSalesStatusId(),
                    $creditProposalEvaluationTransporter->getCurrentUserId(),
                    config('database.version'),
                    $creditProposalEvaluationTransporter->getRevisionData()
                );
            }

            $title = __('translations.credit_proposal.evaluations_has_been_submitted');
            $this->createLogEntry($creditProposalEvaluationTransporter, $title);

            $translatedStatus = __('translations.credit_proposal.status.' . strtolower($status));
            $title = __(
                'translations.credit_proposal.credit_proposal_status_set_by_commission',
                ['status' => $translatedStatus]
            );
            $this->createLogEntry($creditProposalEvaluationTransporter, $title);

            return CustomerCreditProposalResource::make($creditProposal)
                ->additional(
                    [
                        'sales_status' => SalesStatusResource::make(
                            $creditProposal->salesStatus()->with(
                                ['customerLog.customerStatusTitle']
                            )->first()
                        ),
                        'file' => $file ?? [],
                    ]
                );
        });
    }

    private function createLogEntry(
        CustomerCreditProposalEvaluationTransporter $creditProposalEvaluationTransporter,
        string $translation
    ): void {
        $log = [
            'sales_status_id' => $creditProposalEvaluationTransporter->getSalesStatusId(),
            'title' => ucfirst($translation),
            'action' => 'EVENT',
        ];

        event(new CustomerFormSavedEvent($log));
    }

    private function getCreditProposalStatus($evaluations): ?string
    {
        if ($evaluations->firstWhere('status', CustomerCreditProposalEvaluation::STATUS_REJECTED)) {
            return CustomerCreditProposal::STATUS_REJECTED;
        }

        $approvedEvaluations = $evaluations->filter(
            function (CustomerCreditProposalEvaluation $evaluation): bool {
                return $evaluation->status === CustomerCreditProposalEvaluation::STATUS_APPROVED;
            }
        );

        if (count($approvedEvaluations) === count($evaluations)) {
            return CustomerCreditProposal::STATUS_APPROVED;
        }

        return null;
    }
}
