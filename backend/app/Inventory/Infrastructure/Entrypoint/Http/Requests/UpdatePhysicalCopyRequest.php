<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http\Requests;

use App\Inventory\Domain\ValueObject\CopyCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePhysicalCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barcode' => ['sometimes', 'nullable', 'string', 'regex:/^\d{8,32}$/'],
            'condition' => ['sometimes', 'string', Rule::in(CopyCondition::allowed())],
            'cover_photo_url' => ['sometimes', 'nullable', 'string', 'url', 'max:255'],
            'price_cents' => ['sometimes', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
