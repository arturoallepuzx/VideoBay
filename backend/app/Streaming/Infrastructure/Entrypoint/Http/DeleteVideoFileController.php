<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Streaming\Application\DeleteVideoFile\DeleteVideoFile;
use Illuminate\Http\JsonResponse;

class DeleteVideoFileController
{
    public function __construct(
        private DeleteVideoFile $deleteVideoFile,
    ) {}

    public function __invoke(string $videoFileId): JsonResponse
    {
        ($this->deleteVideoFile)($videoFileId);

        return new JsonResponse(null, 204);
    }
}
