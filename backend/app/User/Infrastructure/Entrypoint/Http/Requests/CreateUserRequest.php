<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use App\Shared\Domain\ValueObject\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(UserRole::allowed())],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'avatar_url' => ['nullable', 'string', 'url:http,https', 'max:255'],
        ];
    }
}
