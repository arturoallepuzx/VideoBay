<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http\Requests;

use App\Inventory\Application\ReviewSaleProposal\ReviewSaleProposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewSaleProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                'string',
                Rule::in([ReviewSaleProposal::DECISION_ACCEPT, ReviewSaleProposal::DECISION_REJECT]),
            ],
        ];
    }
}
