<?php

declare(strict_types=1);

namespace App\Streaming\Application\ListVideoFilesForAdmin;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Interfaces\MovieSummaryResolverInterface;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;

class ListVideoFilesForAdmin
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private MovieSummaryResolverInterface $movieSummaryResolver,
    ) {}

    public function __invoke(int $page, int $perPage): ListVideoFilesForAdminResponse
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE));

        $result = $this->videoFileRepository->listForAdmin($page, $perPage);
        $movieSummaries = $this->movieSummaryResolver->resolveMany($this->movieIds($result['items']));

        return ListVideoFilesForAdminResponse::create($result, $movieSummaries);
    }

    /**
     * @param  list<VideoFile>  $videoFiles
     * @return list<Uuid>
     */
    private function movieIds(array $videoFiles): array
    {
        $seen = [];
        $movieIds = [];

        foreach ($videoFiles as $videoFile) {
            $value = $videoFile->movieId()->value();

            if (isset($seen[$value])) {
                continue;
            }

            $seen[$value] = true;
            $movieIds[] = $videoFile->movieId();
        }

        return $movieIds;
    }
}
