<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use App\Shared\Domain\ValueObject\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'role' => ['sometimes', 'string', Rule::in(UserRole::allowed())],
            'avatar_url' => ['sometimes', 'nullable', 'string', 'url:http,https', 'max:255'],
        ];
    }
}
