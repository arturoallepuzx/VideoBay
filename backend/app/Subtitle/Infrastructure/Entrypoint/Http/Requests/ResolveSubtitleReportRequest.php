<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveSubtitleReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:resolved,dismissed'],
        ];
    }
}
