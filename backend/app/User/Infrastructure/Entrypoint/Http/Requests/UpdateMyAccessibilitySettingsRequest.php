<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMyAccessibilitySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accessibility_settings' => ['required', 'array'],
        ];
    }
}
