<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\Services;

use App\Modules\Shop\Application\DTOs\ClientContext;
use App\Modules\Shop\Application\DTOs\Entities\ProductCardData;
use App\Modules\Shop\Application\DTOs\PageElements\PaginatorData;
use App\Modules\Shop\Infrastructure\Persistence\Builders\PaginatorBuilder;
use App\Modules\Shop\Infrastructure\Persistence\Query\ProductIndexQueryRepository;

/**
 * Общий сценарий формирования списка товаров:
 * фильтрация → сортировка → сворачивание модификаций → пагинация → карточки.
 */
readonly class ProductListingResolver
{
    public function __construct(
        private ProductIndexQueryRepository $productIndexQueryRepository,
        private ProductModificationResolver $modificationResolver,
        private ProductCardResolver $productCardResolver,
        private PaginatorBuilder $paginatorBuilder,
    )
    {
    }

    /**
     * @param int[] $allProductIds
     * @return array{
     *     products: ProductCardData[],
     *     paginator: PaginatorData,
     *     total: int,
     *     pageIds: int[]
     * }
     */
    public function resolve(
        array $allProductIds,
        array $params,
        int $page,
        int $perPage,
        ClientContext $clientContext,
    ): array
    {
        $matchedIds = $this->productIndexQueryRepository->getFilteredSortedProductIds($params, $allProductIds);

        $grouped = $this->modificationResolver->resolve($matchedIds, $allProductIds, $clientContext);

        $displayIds = $grouped['displayIds'];
        $variantsByPrimary = $grouped['variantsByPrimary'];

        $total = count($displayIds);
        $pageIds = array_slice($displayIds, ($page - 1) * $perPage, $perPage);

        $products = $this->productCardResolver->resolveCards($pageIds, $clientContext);

        foreach ($products as $card) {
            $card->modifications = $variantsByPrimary[$card->id] ?? [];
        }

        $paginator = $this->paginatorBuilder->build(
            total: $total,
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => '/' . request()->path(),
                'query' => array_diff_key(request()->query(), ['page' => null]),
            ],
        );

        return [
            'products' => $products,
            'paginator' => $paginator,
            'total' => $total,
            'pageIds' => $pageIds,
        ];
    }
}
