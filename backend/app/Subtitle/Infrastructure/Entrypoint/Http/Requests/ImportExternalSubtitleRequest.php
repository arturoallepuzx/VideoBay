<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportExternalSubtitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'in:opensubtitles'],
            'external_id' => ['required', 'string', 'max:100'],
            'file_id' => ['required', 'integer', 'min:1'],
            'language' => ['required', 'string', 'max:10'],
            'label' => ['required', 'string', 'max:80'],
        ];
    }
}
