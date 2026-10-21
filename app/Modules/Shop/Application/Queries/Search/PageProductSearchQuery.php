<?php

namespace App\Modules\Shop\Application\Queries\Search;

use App\Modules\Setting\Application\Actions\GetWebSettingsUseCase;
use App\Modules\Shop\Application\DTOs\ClientContext;
use App\Modules\Shop\Application\DTOs\Elements\ChildrenData;
use App\Modules\Shop\Application\DTOs\PageElements\FilterProductsData;
use App\Modules\Shop\Application\DTOs\PageElements\SeoData;
use App\Modules\Shop\Application\DTOs\Search\ProductSearchPageData;
use App\Modules\Shop\Application\Services\ProductFiltersResolver;
use App\Modules\Shop\Application\Services\ProductListingResolver;
use App\Modules\Shop\Infrastructure\Persistence\Query\CategoryPageQueryRepository;
use App\Modules\Shop\Infrastructure\Persistence\Query\ProductSearchQueryRepository;
use App\Modules\Shop\Infrastructure\Persistence\Query\RoomPageQueryRepository;

readonly class PageProductSearchQuery
{
    public function __construct(
        private CategoryPageQueryRepository $categoryPageQueryRepository,
        private RoomPageQueryRepository $roomPageQueryRepository,
        private ProductSearchQueryRepository $productSearchQueryRepository,
        private ProductListingResolver $listingResolver,
        private ProductFiltersResolver $filtersResolver,
        private GetWebSettingsUseCase $webSettingsUseCase,
    )
    {
    }

    public function execute(string $search, array $params, ClientContext $clientContext): ?ProductSearchPageData
    {
        $web = $this->webSettingsUseCase->execute();

        $allProductIds = $this->productSearchQueryRepository->getProductIdsBySearch($search);
        $perPage = 20;
        $page = (int)($params['page'] ?? 1);

        $rooms = [];
        $categories = [];
        if ($allProductIds) {
            $roomsRaw = $this->categoryPageQueryRepository->getRoomsByProductIds($allProductIds, $params);
            $rooms = array_map(
                fn(\stdClass $r) => new ChildrenData(id: (int)$r->id, name: $r->name, slug: $r->slug),
                $roomsRaw,
            );
            $categoriesRaw = $this->roomPageQueryRepository->getCategoriesByProductIds($allProductIds, $params);
            $categories = array_map(
                fn(\stdClass $r) => new ChildrenData(id: (int)$r->id, name: $r->name, slug: $r->slug),
                $categoriesRaw,
            );
        }

        $listing = $this->listingResolver->resolve($allProductIds, $params, $page, $perPage, $clientContext);

        $filters = $this->getFilters(
            array_map(fn(ChildrenData $cat) => $cat->id, $categories),
            $allProductIds,
            $params,
        );

        $meta = new SeoData($search . ' Интернет Магазин', '');
        $meta->ogSiteName = $web->web_name;

        return new ProductSearchPageData(
            products: $listing['products'],
            paginator: $listing['paginator'],
            filters: $filters,
            meta: $meta,
            search: $search,
        );
    }

    private function getFilters(array $categoryIds, array $productIds, array $params): FilterProductsData
    {
        $aggregates = $this->filtersResolver->resolveAggregates($categoryIds, $productIds);

        return $this->filtersResolver->buildFilterProductsData($aggregates, $params);
    }
}
