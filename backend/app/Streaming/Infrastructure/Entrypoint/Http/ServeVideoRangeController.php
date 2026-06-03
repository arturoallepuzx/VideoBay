<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Streaming\Application\ServeVideoRange\ServeVideoRange;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ServeVideoRangeController
{
    public function __construct(
        private ServeVideoRange $serveVideoRange,
    ) {}

    public function __invoke(string $videoFileId): BinaryFileResponse
    {
        $result = ($this->serveVideoRange)($videoFileId);

        $response = new BinaryFileResponse($result->absolutePath);
        $response->headers->set('Content-Type', $result->mimeType);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE);

        return $response;
    }
}
