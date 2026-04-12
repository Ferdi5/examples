<?php

declare(strict_types=1);

namespace App\Domains\Integrations\Creditsafe\Requests;

final class CreditsafeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requestSource' => 'required|string',
        ];
    }
}
