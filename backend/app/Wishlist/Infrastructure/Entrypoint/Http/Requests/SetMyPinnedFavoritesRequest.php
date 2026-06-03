<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetMyPinnedFavoritesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slots' => ['present', 'array'],
            'slots.*.position' => ['required', 'integer', 'min:1'],
            'slots.*.movie_uuid' => ['required', 'string', 'uuid'],
        ];
    }
}
