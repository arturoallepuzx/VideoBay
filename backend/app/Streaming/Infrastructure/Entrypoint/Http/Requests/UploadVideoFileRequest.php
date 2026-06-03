<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadVideoFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'video' => ['required', 'file', 'max:102400', 'mimes:mp4,mkv,avi,mov'],
            'movie_uuid' => ['required_without:tmdb_id', 'uuid'],
            'tmdb_id' => ['required_without:movie_uuid', 'integer', 'min:1'],
        ];
    }
}
