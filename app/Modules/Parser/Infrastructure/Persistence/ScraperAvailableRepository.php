<?php

declare(strict_types=1);

namespace App\Modules\Parser\Infrastructure\Persistence;

use App\Modules\Parser\Domain\Entities\ScraperAvailableEntity;
use App\Modules\Parser\Domain\Interfaces\ScraperAvailableRepositoryInterface;
use App\Modules\Parser\Infrastructure\Models\ScraperAvailable;

class ScraperAvailableRepository implements ScraperAvailableRepositoryInterface
{
    public function save(ScraperAvailableEntity $available): ScraperAvailableEntity
    {
        $model = $available->id
            ? ScraperAvailable::findOrFail($available->id)
            : new ScraperAvailable();

        $model->parser_category_id = $available->parserCategoryId;
        $model->brand_id = $available->brandId;
        $model->url = $available->url;

        $model->save();

        return $this->hydrate($model);
    }

    public function getById(int $id): ScraperAvailableEntity
    {
        return $this->hydrate(ScraperAvailable::findOrFail($id));
    }

    public function delete(int $id): void
    {
        ScraperAvailable::findOrFail($id)->delete();
    }

    public function findFirstByBrandId(int $brandId): ?ScraperAvailableEntity
    {
        $model = ScraperAvailable::where('brand_id', $brandId)
            ->orderBy('id')
            ->first();

        return $model ? $this->hydrate($model) : null;
    }

    public function existsByBrandId(int $brandId): bool
    {
        return ScraperAvailable::where('brand_id', $brandId)->exists();
    }

    private function hydrate(ScraperAvailable $model): ScraperAvailableEntity
    {
        $entity = new ScraperAvailableEntity(
            parserCategoryId: $model->parser_category_id,
            brandId: $model->brand_id,
            url: $model->url,
        );

        $entity->id = $model->id;

        return $entity;
    }
}
