<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_prices_cents' => ['nullable', 'array'],
            'base_prices_cents.*' => ['integer', 'min:0'],

            'condition_multipliers' => ['nullable', 'array'],
            'condition_multipliers.*' => ['numeric', 'min:0'],

            'buy_margin_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
