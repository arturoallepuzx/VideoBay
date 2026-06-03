<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadUserSubtitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subtitle' => ['required', 'file', 'max:5120', 'extensions:srt,vtt'],
            'language' => ['required', 'string', 'max:10'],
            'label' => ['nullable', 'string', 'max:80'],
        ];
    }
}
