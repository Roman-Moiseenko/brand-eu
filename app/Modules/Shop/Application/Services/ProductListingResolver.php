<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\Services;

use App\Modules\Shop\Application\DTOs\ClientContext;
use App\Modules\Shop\Application\DTOs\Entities\ProductCardData;
use App\Modules\Shop\Application\DTOs\Entities\ProductModificationData;
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
        $variantIdsByPrimary = $grouped['variantIdsByPrimary'];
        $variantNamesByProduct = $grouped['variantNamesByProduct'];
        $variantImagesByProduct = $grouped['variantImagesByProduct'];

        $total = count($displayIds);
        $pageIds = array_slice($displayIds, ($page - 1) * $perPage, $perPage);

        // Собираем ID всех карточек текущей страницы (базовые + их варианты),
        // чтобы сделать ОДИН вызов loadProductCards в конце.
        $variantIdsForPage = [];
        foreach ($pageIds as $id) {
            $variantIdsForPage = array_merge($variantIdsForPage, $variantIdsByPrimary[$id] ?? []);
        }
        $allCardIds = array_values(array_unique(array_merge($pageIds, $variantIdsForPage)));

        $cards = $this->productCardResolver->resolveCards($allCardIds, $clientContext);

        $cardsById = [];
        foreach ($cards as $card) {
            $cardsById[$card->id] = $card;
        }

        $products = [];
        foreach ($pageIds as $id) {
            if (isset($cardsById[$id])) {
                $products[] = $cardsById[$id];
            }
        }

        foreach ($products as $card) {
            $card->modifications = $this->buildModifications(
                $card->id,
                $variantIdsByPrimary,
                $cardsById,
                $variantNamesByProduct,
                $variantImagesByProduct,
            );
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

    /**
     * @param array<int, int[]> $variantIdsByPrimary
     * @param array<int, ProductCardData> $cardsById
     * @param array<int, string> $variantNamesByProduct
     * @param array<int, ?string> $variantImagesByProduct
     * @return ProductModificationData[]
     */
    private function buildModifications(
        int $primaryId,
        array $variantIdsByPrimary,
        array $cardsById,
        array $variantNamesByProduct,
        array $variantImagesByProduct,
    ): array
    {
        $variantIds = $variantIdsByPrimary[$primaryId] ?? [];
        if (empty($variantIds)) {
            return [];
        }

        $modifications = [];
        foreach ($variantIds as $variantId) {
            $card = $cardsById[$variantId] ?? null;
            if ($card === null) {
                continue;
            }

            $modifications[] = ProductModificationData::fromProductCardData(
                $card,
                $variantNamesByProduct[$variantId] ?? null,
                $variantImagesByProduct[$variantId] ?? null,
            );
        }

        return $modifications;
    }
}
