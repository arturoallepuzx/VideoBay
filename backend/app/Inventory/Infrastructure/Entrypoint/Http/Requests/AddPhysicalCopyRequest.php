<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http\Requests;

use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Inventory\Domain\ValueObject\CopyFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPhysicalCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movie_id' => ['required', 'string', 'uuid'],
            'sku' => ['required', 'string', 'max:64'],
            'barcode' => ['nullable', 'string', 'regex:/^\d{8,32}$/'],
            'format' => ['required', 'string', Rule::in(CopyFormat::allowed())],
            'region' => ['nullable', 'string', 'max:16'],
            'condition' => ['required', 'string', Rule::in(CopyCondition::allowed())],
            'cover_photo_url' => ['nullable', 'string', 'url', 'max:255'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'stock_available' => ['required', 'integer', 'min:0'],
        ];
    }
}
