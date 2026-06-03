<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListPendingSubtitleReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
