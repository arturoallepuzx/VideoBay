<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Streaming\Application\ServeVideoRange\ServeVideoRange;
use App\Streaming\Application\ServeVideoRange\ServeVideoRangeResponse;
use App\Streaming\Domain\Exception\InvalidVideoRangeException;
use App\Streaming\Infrastructure\Services\RangeHeaderParser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServeVideoRangeController
{
    public function __construct(
        private ServeVideoRange $serveVideoRange,
        private RangeHeaderParser $rangeHeaderParser,
    ) {}

    public function __invoke(Request $request, string $videoFileId): Response
    {
        $specification = $this->rangeHeaderParser->parse($request->headers->get('Range'));

        try {
            $result = ($this->serveVideoRange)($videoFileId, $specification);
        } catch (InvalidVideoRangeException $e) {
            return new Response('', Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, [
                'Accept-Ranges' => 'bytes',
                'Content-Range' => sprintf('bytes */%d', $e->totalSizeBytes()),
            ]);
        }

        return new StreamedResponse(
            $this->chunkWriterFor($result),
            $result->byteRange === null ? Response::HTTP_OK : Response::HTTP_PARTIAL_CONTENT,
            $this->headersFor($result),
        );
    }

    /** @return array<string, string> */
    private function headersFor(ServeVideoRangeResponse $result): array
    {
        $headers = [
            'Content-Type' => $result->mimeType,
            'Accept-Ranges' => 'bytes',
            'Content-Length' => (string) ($result->byteRange?->length() ?? $result->totalSizeBytes),
        ];

        if ($result->byteRange !== null) {
            $headers['Content-Range'] = sprintf(
                'bytes %d-%d/%d',
                $result->byteRange->start(),
                $result->byteRange->end(),
                $result->totalSizeBytes,
            );
        }

        return $headers;
    }

    private function chunkWriterFor(ServeVideoRangeResponse $result): \Closure
    {
        return function () use ($result): void {
            foreach ($result->chunks as $chunk) {
                echo $chunk;
                flush();
            }
        };
    }
}
