<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveBarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'regex:/^\d{8,32}$/'],
        ];
    }
}
