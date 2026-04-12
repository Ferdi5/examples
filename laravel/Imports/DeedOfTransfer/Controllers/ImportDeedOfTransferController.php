<?php

declare(strict_types=1);

namespace App\Domains\Imports\DeedOfTransfer\Controllers;

use App\Domains\Imports\DeedOfTransfer\Actions\ImportDeedOfTransferAction;

final class ImportDeedOfTransferController extends Controller
{
    public function store(Request $request): ?RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:51200'],
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getSheet(0);

        $customerNumber = $sheet->getCell('A3')->getValue();

        if (!$customerNumber || !is_string($customerNumber)) {
            Log::error('Incorrect customer number format', [
                'customer_number' => $customerNumber,
            ]);

            return null;
        }

        $administration = Administration::where('customer_number', $customerNumber)->first();

        if (!$administration?->id) {
            Log::error('No administration found', [
                'customer_number' => $customerNumber,
            ]);

            return null;
        }

        try {
            Excel::import(
                new ImportDeedOfTransferAction(
                    $administration->id,
                    $customerNumber,
                    $fileName
                ),
                $request->file('file')
            );
        } catch (ValidationException $e) {
            Log::error('The Excel file contains invalid data', [
                'file_name' => $fileName,
                'customer_number' => $customerNumber,
            ]);

            return null;
        } catch (SpreadsheetException $e) {
            Log::error('The uploaded file is not a valid Excel document', [
                'file_name' => $fileName,
                'customer_number' => $customerNumber,
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Failed to import the file', [
                'file_name' => $fileName,
                'customer_number' => $customerNumber,
            ]);

            report($e);

            return null;
        }

        return null;
    }
}
