<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
            'avatar_url' => ['sometimes', 'nullable', 'string', 'url:http,https', 'max:255'],
        ];
    }
}
