<?php

declare(strict_types=1);

namespace App\Domains\Imports\DBasics\Controllers;

use App\Domains\Imports\DBasics\Actions\DebtorImportAction;

final class ImportDBasicsDebtorController extends Controller
{
    public function store(Request $request): ?RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:txt', 'max:51200'],
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        $customerNumber = Str::contains($fileName, '-')
            ? Str::before($fileName, '-')
            : null;

        if (!$customerNumber || !is_string($customerNumber)) {
            Log::error('Incorrect customer number format', [
                'customer_number' => $customerNumber,
            ]);

            return null;
        }

        $administration = Administration::where('customer_number', $customerNumber)->first();

        if (!$administration) {
            Log::error('No administration found', [
                'customer_number' => $customerNumber,
            ]);

            return null;
        }

        try {
            Excel::import(
                new DebtorImportAction($administration->id, $customerNumber, $fileName),
                $file,
                null,
                \Maatwebsite\Excel\Excel::TSV
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
