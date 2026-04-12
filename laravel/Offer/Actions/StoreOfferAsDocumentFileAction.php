<?php

namespace App\Domains\Offer;

final class StoreOfferAsDocumentFileAction
{
    public function __construct(
        private readonly SalesStatusRepository $salesStatusRepository,
        private readonly GeneratePdfAction $generatePdfAction
    ) {
    }

    public function execute(
        SalesStatus $salesStatus,
        string $type,
        User $user,
        ListOfferCertaintiesAction $listOfferCertaintiesAction,
        ListOfferRequiredDocumentsAction $listOfferRequiredDocumentsAction
    ): array {
        return DB::transaction(
            function () use (
                $salesStatus,
                $type,
                $user,
                $listOfferCertaintiesAction,
                $listOfferRequiredDocumentsAction
            ): array {
                $document = $salesStatus->customerDocuments;

                if (!$document?->id) {
                    $document = CustomerDocuments::query()->create(
                        [
                            'sales_status_id' => $salesStatus->id,
                        ]
                    );
                }

                $file = $document->files()->create(
                    [
                        'user_id' => $user->id,
                        'category' => Files::CATEGORY_OFFER,
                        'virtual_path' => 'documents/prospects/' . $salesStatus->id . '/' . strtolower(Files::CATEGORY_OFFER),
                        'locked' => true,
                    ]
                );

                $pdf = $this->generatePDF(
                    $salesStatus,
                    $type,
                    $listOfferCertaintiesAction,
                    $listOfferRequiredDocumentsAction,
                    $file->id
                );

                $fileName = __('translations.offer_preview.offer') . '-' . getDocumentReference(
                    $salesStatus->id,
                    $file->id
                ) . '.pdf';

                $documentFile = (new StoreGeneratedFileAction)->run(
                    $file,
                    $fileName,
                    $pdf,
                );

                $this->createLogEntry($salesStatus->offer, $documentFile->file_name);

                $salesStatus = $this->salesStatusRepository->fetchSalesStatusWithLog($salesStatus->id);

                return [
                    'data' => $documentFile,
                    'sales_status' => $salesStatus,
                    'customer_document_id' => $document->id,
                ];
            }
        );
    }

    private function generatePDF(
        SalesStatus $salesStatus,
        string $type,
        ListOfferCertaintiesAction $listOfferCertaintiesAction,
        ListOfferRequiredDocumentsAction $listOfferRequiredDocumentsAction,
        int $fileId
    ) {
        $viewData = new GetOfferDocumentData(
            $listOfferCertaintiesAction, $listOfferRequiredDocumentsAction
        )->execute(
            $salesStatus->offer,
            'pdf',
            false
        );
        $viewData['reference'] = getDocumentReference($salesStatus->id, $fileId);

        if ($type === 'formal') {
            $view = view('documents.offer-formal', $viewData);
        } else {
            $view = view('documents.offer-informal', $viewData);
        }

        return $this->generatePdfAction->execute($view);
    }

    private function createLogEntry(CustomerOfferDetails $offer, string $fileName): void
    {
        $log = [
            'sales_status_id' => $offer->salesStatus->id,
            'title' => ucfirst(__('translations.file.offer_document_has_been_created')),
            'message' => ucfirst(
                __(
                    'translations.file.offer_document_has_been_created_with_filename',
                    [
                        'file_name' => $fileName,
                    ]
                )
            ),
            'action' => 'EVENT',
        ];

        event(new CustomerFormSavedEvent($log));
    }
}
