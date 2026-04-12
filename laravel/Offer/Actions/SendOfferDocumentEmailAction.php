<?php

namespace App\Domains\Offer;

class SendOfferDocumentEmailAction
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
        ListOfferRequiredDocumentsAction $listOfferRequiredDocumentsAction,
        array $to,
        ?array $cc,
        ?array $bcc,
        ?string $message,
    ): array {
        $file = new StoreOfferAsDocumentFileAction($this->salesStatusRepository, $this->generatePdfAction)->execute(
            $salesStatus,
            $type,
            $user,
            $listOfferCertaintiesAction,
            $listOfferRequiredDocumentsAction,
        );

        $file['data']->file_name = 'offerte-' . str_replace(
                ' ',
                '-',
                strtolower($salesStatus->company->name)
            ) . '.pdf';

        $this->dispatchEmail(
            $to,
            $cc,
            $bcc,
            $file,
            $user,
            $message
        );

        $this->createLogEntry(
            $salesStatus->offer,
            $file['data']->file_name,
            $to,
            $cc,
            $bcc,
            $message
        );

        $file['sales_status'] = $this->salesStatusRepository->fetchSalesStatusWithLog($salesStatus->id);

        return $file;
    }

    private function dispatchEmail(
        array $to,
        ?array $cc,
        ?array $bcc,
        User $user,
        File $file,
        ?string $message,
    ) {
        $to = $this->getEmailAddresses($to);
        $cc = $this->getEmailAddresses($cc);
        $bcc = $this->getEmailAddresses($bcc);
        $bcc[] = config('impactfactoring.email.offer');

        dispatch(
            new SendEmailMessageJob(
                'emails.offer-document',
                $to,
                __('translations.email.offer.subject'),
                $message,
                [$file['data']],
                $user->email,
                config('impactfactoring.email.sales'),
                $cc,
                $bcc,
            )
        );
    }

    private function createLogEntry(
        CustomerOfferDetails $offer,
        string $fileName,
        array $to,
        ?array $cc,
        ?array $bcc,
        ?string $message,
    ): void
    {
        $messageTranslation = $this->getLogMessageTranslation(
            $fileName,
            $to,
            $cc,
            $bcc,
            __('translations.email.offer.subject'),
            $message
        );

        $log = [
            'sales_status_id' => $offer->salesStatus->id,
            'title' => ucfirst(__('translations.file.offer_document_has_been_send')),
            'message' => $messageTranslation,
            'action' => 'EVENT',
        ];

        event(new CustomerFormSavedEvent($log));
    }

    private function getLogMessageTranslation(
        string $fileName,
        $to,
        $cc,
        $bcc,
        $subject,
        $message
    ): string {
        $from = __('translations.email.from') . ': ' . config('impactfactoring.email.sales');
        $to = "\r\n" . __('translations.email.to') . ': ' . implode(', ', $to);
        $cc = count($cc) ? "\r\n" . __('translations.email.cc') . ': ' . implode(', ', $cc) : '';
        $bcc = count($bcc) ? "\r\n" . __('translations.email.bcc') . ': ' . implode(', ', $bcc) : '';
        $attachment = "\r\n" . __('translations.email.attachment') . ': ' . $fileName;
        $subject = "\r\n" . __('translations.email.subject') . ': ' . $subject;
        $message = "\r\n" . __('translations.email.message') . ':' . "\r\n\r\n" . $message;

        return $from . $to . $cc . $bcc . $attachment . $subject . $message;
    }

    private function getEmailAddresses(array $personIds): array
    {
        if (!count($personIds)) {
            return [];
        }

        $persons = Person::query()
            ->whereIn('id', $personIds)
            ->orderByRaw('FIELD(id, ' . implode(', ', $personIds) . ')')
            ->with(['companyPersonContact.contactDetail', 'contactDetail'])
            ->get();

        $emails = $persons->map(static function (Person $person): array {
            $emails[] = $person?->contactDetail->email_address ?? $person?->companyPersonContact?->contactDetail->email_address;

            return $emails;
        });

        return $emails->flatten()->unique()->toArray();
    }
}
