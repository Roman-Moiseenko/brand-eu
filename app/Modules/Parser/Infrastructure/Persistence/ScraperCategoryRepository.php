<?php

declare(strict_types=1);

namespace App\Modules\Parser\Infrastructure\Persistence;

use App\Modules\Parser\Domain\Entities\ScraperCategoryEntity;
use App\Modules\Parser\Domain\Interfaces\ScraperCategoryRepositoryInterface;
use App\Modules\Parser\Infrastructure\Models\ScraperCategory;

class ScraperCategoryRepository implements ScraperCategoryRepositoryInterface
{
    public function save(ScraperCategoryEntity $category): ScraperCategoryEntity
    {
        $model = $category->id
            ? ScraperCategory::findOrFail($category->id)
            : new ScraperCategory();

        $model->parser_category_id = $category->parserCategoryId;
        $model->brand_id = $category->brandId;
        $model->url = $category->url;

        $model->save();

        return $this->hydrate($model);
    }

    public function getById(int $id): ScraperCategoryEntity
    {
        return $this->hydrate(ScraperCategory::findOrFail($id));
    }

    public function delete(int $id): void
    {
        ScraperCategory::findOrFail($id)->delete();
    }

    public function existsByBrandId(int $brandId): bool
    {
        return ScraperCategory::where('brand_id', $brandId)->exists();
    }

    public function findFirstByBrandId(int $brandId): ?ScraperCategoryEntity
    {
        $model = ScraperCategory::where('brand_id', $brandId)
            ->orderBy('id')
            ->first();

        return $model ? $this->hydrate($model) : null;
    }

    private function hydrate(ScraperCategory $model): ScraperCategoryEntity
    {
        $entity = new ScraperCategoryEntity(
            parserCategoryId: $model->parser_category_id,
            brandId: $model->brand_id,
            url: $model->url,
        );

        $entity->id = $model->id;

        return $entity;
    }
}
