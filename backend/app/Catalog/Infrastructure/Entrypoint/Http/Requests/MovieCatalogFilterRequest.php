<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovieCatalogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'genre' => ['nullable', 'string', 'max:64'],
            'year_from' => ['nullable', 'integer', 'min:1888', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1888', 'max:2100'],
            'sort' => ['nullable', 'string', 'in:newest,title,rating'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
