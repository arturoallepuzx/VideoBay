<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:10'],
            'body' => ['nullable', 'string', 'max:5000'],
            'contains_spoilers' => ['nullable', 'boolean'],
        ];
    }
}
