<?php

namespace App\Modules\Catalog\Application\Actions\Series;

use App\Modules\Catalog\Domain\Entities\SeriesEntity;
use App\Modules\Catalog\Domain\Interfaces\SeriesRepositoryInterface;

class FindSeriesByNameProductQuery
{

    public function __construct(
        private SeriesRepositoryInterface         $seriesRepository,

    )
    {

    }

    public function execute(string $productName): ?SeriesEntity
    {
        $seriesEntities = $this->seriesRepository->getAll();
        $series = [];
        foreach ($seriesEntities as $seriesEntity) {
            if ($seriesEntity->name === '') {
                continue;
            }
            if (mb_stripos($productName, $seriesEntity->name) !== false) {
                $series[] = $seriesEntity;
            }
        }
        if (empty($series)) {
            return null;
        }

        $longest = $series[0];
        foreach ($series as $seriesEntity) {
            if (mb_strlen($seriesEntity->name) > mb_strlen($longest->name)) {
                $longest = $seriesEntity;
            }
        }

        return $longest;
    }
}
