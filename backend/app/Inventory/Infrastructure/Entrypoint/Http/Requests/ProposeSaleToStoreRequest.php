<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http\Requests;

use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Inventory\Domain\ValueObject\CopyFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProposeSaleToStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movie_id' => ['nullable', 'string', 'uuid'],
            'title_text' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'regex:/^\d{8,32}$/'],
            'format' => ['required', 'string', Rule::in(CopyFormat::allowed())],
            'condition' => ['required', 'string', Rule::in(CopyCondition::allowed())],
            'notes' => ['nullable', 'string', 'max:2000'],
            'offered_price_cents' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
