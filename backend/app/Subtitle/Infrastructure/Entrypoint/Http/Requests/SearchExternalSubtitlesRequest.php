<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchExternalSubtitlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'language' => ['nullable', 'string', 'max:10'],
        ];
    }
}
