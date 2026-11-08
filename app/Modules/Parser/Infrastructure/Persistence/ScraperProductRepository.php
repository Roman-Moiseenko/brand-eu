<?php

declare(strict_types=1);

namespace App\Modules\Parser\Infrastructure\Persistence;

use App\Modules\Parser\Domain\Entities\ScraperProductEntity;
use App\Modules\Parser\Domain\Interfaces\ScraperProductRepositoryInterface;
use App\Modules\Parser\Infrastructure\Models\ScraperProduct;

class ScraperProductRepository implements ScraperProductRepositoryInterface
{
    public function save(ScraperProductEntity $product): ScraperProductEntity
    {
        $model = $product->id
            ? ScraperProduct::findOrFail($product->id)
            : new ScraperProduct();

        $model->parser_category_id = $product->parserCategoryId;
        $model->brand_id = $product->brandId;
        $model->url = $product->url;

        $model->save();

        return $this->hydrate($model);
    }

    public function getById(int $id): ScraperProductEntity
    {
        return $this->hydrate(ScraperProduct::findOrFail($id));
    }

    public function delete(int $id): void
    {
        ScraperProduct::findOrFail($id)->delete();
    }

    public function findFirstByBrandId(int $brandId): ?ScraperProductEntity
    {
        $model = ScraperProduct::where('brand_id', $brandId)
            ->orderBy('id')
            ->first();

        return $model ? $this->hydrate($model) : null;
    }

    private function hydrate(ScraperProduct $model): ScraperProductEntity
    {
        $entity = new ScraperProductEntity(
            parserCategoryId: $model->parser_category_id,
            brandId: $model->brand_id,
            url: $model->url,
        );

        $entity->id = $model->id;

        return $entity;
    }
}
