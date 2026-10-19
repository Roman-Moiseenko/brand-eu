<?php

declare(strict_types=1);

namespace App\Modules\Parser\Infrastructure\Persistence;

use App\Modules\Parser\Application\DTOs\Product\ParserProductFilterData;
use App\Modules\Parser\Domain\Entities\ParserProductEntity;
use App\Modules\Parser\Domain\Entities\ParserProductSizeEntity;
use App\Modules\Parser\Domain\Interfaces\ParserProductRepositoryInterface;
use App\Modules\Parser\Infrastructure\Models\ParserProduct;
use App\Modules\Parser\Infrastructure\Models\ParserProductSizes;
use Illuminate\Pagination\LengthAwarePaginator;

class ParserProductRepository implements ParserProductRepositoryInterface
{

    public function save(ParserProductEntity $product): ParserProductEntity
    {
        $model = $product->id
            ? ParserProduct::findOrFail($product->id)
            : new ParserProduct();

        $model->name = $product->name;
        $model->code = $product->code;
        $model->url = $product->url;
        $model->price_base = $product->priceBase;
        $model->price_sell = $product->priceSell;
        $model->availability = $product->availability;
        $model->product_id = $product->productId;
        $model->brand_id = $product->brandId;
        $model->maker_id = $product->makerId;
        $model->category_id = $product->categoryId;
        $model->data = $product->data !== '' ? $product->data : null;

        $model->save();

        // sizes — сохраняются вместе с ParserProduct
        if ($product->sizes !== []) {
            $model->sizes()->delete();

            foreach ($product->sizes as $sizeEntity) {
                $size = new ParserProductSizes();
                $size->product_id = $model->id;
                $size->size_original = $sizeEntity->sizeOriginal;
                $size->size = $sizeEntity->size;
                $size->product_size_id = $sizeEntity->productSizeId;
                $size->available = $sizeEntity->available;
                $size->url = $sizeEntity->url;
                $size->sku = $sizeEntity->sku;
                $size->price = $sizeEntity->price;
                $size->save();
            }
        }

        $model->refresh();

        return $this->hydrate($model);
    }

    public function getById(int $id): ParserProductEntity
    {
        $model = ParserProduct::with('sizes.productSize')->findOrFail($id);
        return $this->hydrate($model);
    }

    public function getByCode(string $code): ?ParserProductEntity
    {
        $model = ParserProduct::with('sizes.productSize')->where('code', $code)->first();

        if ($model === null) {
            return null;
        }
        return $this->hydrate($model);
    }

    public function existsByCode(string $code): bool
    {
         $product = ParserProduct::where('code', $code)->exists();
         if ($product) return true;

         return ParserProductSizes::where('sku', $code)->exists();
    }

    public function getAll(): array
    {
        return ParserProduct::with('sizes.productSize')->get()
            ->map(fn(ParserProduct $model) => $this->hydrate($model))
            ->toArray();
    }

    public function getAllId(): array
    {
        return ParserProduct::where('availability', true)
            ->pluck('id')
            ->toArray();
    }

    public function getByProductId(int $productId): ?ParserProductEntity
    {
        $model = ParserProduct::with('sizes.productSize')->where('product_id', $productId)->first();
        if (is_null($model)) return null;
        return $this->hydrate($model);
    }

    public function delete(int $id): void
    {
        $model = ParserProduct::findOrFail($id);
        $model->delete();
    }

    /**
     * @return ParserProductEntity[]
     */
    public function getByCategoryIds(array $categoryIds): array
    {
        return ParserProduct::with('sizes.productSize')
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('id', $categoryIds);
            })
            ->get()
            ->map(fn(ParserProduct $model) => $this->hydrate($model))
            ->values()
            ->toArray();
    }

    public function bulkToggleAvailability(array $productIds, bool $availability): void
    {
        if (empty($productIds)) {
            return;
        }

        ParserProduct::whereIn('id', $productIds)->update(['availability' => $availability]);
    }

    public function getFilteredPaginated(ParserProductFilterData &$filter): LengthAwarePaginator
    {
        $query = ParserProduct::with('sizes.productSize')->orderBy('name');
        $filter->count = 0;
        if ($filter->code !== null) {
            $code = $filter->code;
            $query->where(function ($q) use ($code) {
                $q->whereRaw("LOWER(name) like LOWER('%$code%')")
                    ->orWhere('code', 'like', "%$code%");
            });
            $filter->count++;
        }

        if ($filter->show !== null) {
            match ($filter->show) {
                'availability' => $query->where('availability', true),
                'not_availability' => $query->where('availability', false),
                default => null,
            };
            $filter->count++;
        }

        return $query->paginate($filter->perPage)
            ->withQueryString()
            ->through(fn(ParserProduct $model) => $this->hydrate($model));
    }

    public function findAllByCategoryId(int $categoryId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return ParserProduct::with('sizes.productSize')
            ->orderBy('name')
            ->whereHas('categories', fn($query) => $query->where('id', $categoryId))
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn(ParserProduct $model) => $this->hydrate($model));
    }

    private function hydrate(ParserProduct $model): ParserProductEntity
    {
        $entity = new ParserProductEntity(
            name: $model->name,
            code: $model->code,
        );

        $entity->id = $model->id;
        $entity->productId = $model->product_id;
        $entity->brandId = $model->brand_id;
        $entity->url = $model->url ?? '';
        $entity->priceBase = (float) ($model->price_base ?? 0);
        $entity->priceSell = (float) ($model->price_sell ?? 0);
        $entity->makerId = $model->maker_id ?? '';
        $entity->data = $model->data ?? '';
        $entity->availability = (bool) ($model->availability ?? false);
        $entity->categoryId = $model->category_id;

        $entity->sizes = $this->hydrateSizes($model);

        return $entity;
    }

    /**
     * @return ParserProductSizeEntity[]
     */
    private function hydrateSizes(ParserProduct $model): array
    {
        if (!$model->relationLoaded('sizes')) {
            return [];
        }

        return $model->sizes->map(function (ParserProductSizes $size) {
            $entity = new ParserProductSizeEntity();

            $entity->id = $size->id;
            $entity->productId = $size->product_id;
            $entity->sizeOriginal = $size->size_original ?? '';
            $entity->size = $size->size ?? '';
            $entity->productSizeId = $size->product_size_id;
            $entity->available = (bool) ($size->available ?? true);
            $entity->url = $size->url ?? '';
            $entity->sku = $size->sku ?? '';
            $entity->price = (float) ($size->price ?? 0);
            $entity->values = $size->relationLoaded('productSize') && $size->productSize
                ? (array) $size->productSize->values
                : [];

            return $entity;
        })->values()->toArray();
    }
}
