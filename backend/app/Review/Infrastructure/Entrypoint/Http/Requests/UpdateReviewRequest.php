<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'body' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'contains_spoilers' => ['sometimes', 'boolean'],
        ];
    }
}
