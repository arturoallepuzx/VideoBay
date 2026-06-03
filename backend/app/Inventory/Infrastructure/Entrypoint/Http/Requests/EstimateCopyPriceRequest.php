<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http\Requests;

use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Inventory\Domain\ValueObject\CopyFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstimateCopyPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'string', Rule::in(CopyFormat::allowed())],
            'condition' => ['required', 'string', Rule::in(CopyCondition::allowed())],
        ];
    }
}
