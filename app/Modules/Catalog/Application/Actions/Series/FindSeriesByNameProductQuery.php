<?php

namespace App\Modules\Catalog\Application\Actions\Series;

use App\Modules\Catalog\Domain\Entities\SeriesEntity;
use App\Modules\Catalog\Domain\Interfaces\SeriesRepositoryInterface;

readonly class FindSeriesByNameProductQuery
{
    public function __construct(
        private SeriesRepositoryInterface $seriesRepository,
    )
    {
    }

    public function execute(string $productName):? SeriesEntity
    {
        $seriesEntities = $this->seriesRepository->getAll();
        return array_find($seriesEntities, fn($seriesEntity) => str_contains($productName, $seriesEntity->name));
    }
}
