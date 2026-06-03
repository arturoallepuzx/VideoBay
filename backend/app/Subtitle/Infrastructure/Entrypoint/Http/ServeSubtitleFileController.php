<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Entrypoint\Http;

use App\Subtitle\Application\ServeSubtitleFile\ServeSubtitleFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ServeSubtitleFileController
{
    public function __construct(private ServeSubtitleFile $serveSubtitleFile) {}

    public function __invoke(string $subtitleId): BinaryFileResponse
    {
        $response = ($this->serveSubtitleFile)($subtitleId);

        $file = new BinaryFileResponse($response->absolutePath);
        $file->headers->set('Content-Type', $response->mimeType);
        $file->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE);

        return $file;
    }
}
