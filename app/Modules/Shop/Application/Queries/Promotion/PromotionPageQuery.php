<?php

namespace App\Modules\Shop\Application\Queries\Promotion;

use App\Modules\Setting\Application\Actions\GetWebSettingsUseCase;
use App\Modules\Shop\Application\DTOs\ClientContext;
use App\Modules\Shop\Application\DTOs\Elements\ChildrenData;
use App\Modules\Shop\Application\DTOs\PageElements\FilterProductsData;
use App\Modules\Shop\Application\DTOs\PageElements\OgImage;
use App\Modules\Shop\Application\DTOs\Pages\ProductIndexPageData;
use App\Modules\Shop\Application\Services\ProductFiltersResolver;
use App\Modules\Shop\Application\Services\ProductListingResolver;
use App\Modules\Shop\Infrastructure\Persistence\Builders\SchemaBuilder;
use App\Modules\Shop\Infrastructure\Persistence\CacheInvalidationRegistry;
use App\Modules\Shop\Infrastructure\Persistence\Query\ContentBlockQueryRepository;
use App\Modules\Shop\Infrastructure\Persistence\Query\PromotionPageQueryRepository;
use App\Modules\Shop\Infrastructure\Persistence\Query\RoomPageQueryRepository;
use App\Modules\Shop\Infrastructure\Persistence\SeoAdapter;

readonly class PromotionPageQuery
{
    public function __construct(
        private PromotionPageQueryRepository $repository,
        private RoomPageQueryRepository $roomRepository,
        private ProductListingResolver $listingResolver,
        private ProductFiltersResolver $filtersResolver,
        private SeoAdapter $seoAdapter,
        private SchemaBuilder $schemaBuilder,
        private ContentBlockQueryRepository $blockRepository,
        private GetWebSettingsUseCase $webSettingsUseCase,
    )
    {
    }

    public function execute(string $slug, array $params, ClientContext $clientContext): ?ProductIndexPageData
    {
        $web = $this->webSettingsUseCase->execute();
        $mainInfo = $this->repository->getPromotion($slug);
        if (is_null($mainInfo)) return null;

        $key_cache = str_replace('{id}', (string)$mainInfo->id, CacheInvalidationRegistry::PROMOTION_PRODUCTS_ID);

        $perPage = 20;
        $page = (int)($params['page'] ?? 1);
        $allProductIds = cache_remember(
            $key_cache,
            now()->addDay(),
            fn() => $this->repository->getProductIdsInPromotion($mainInfo->id),
        );

        $categories = [];
        if ($allProductIds) {
            $categoriesRaw = $this->roomRepository->getCategoriesByProductIds($allProductIds, $params);
            $categories = array_map(
                fn(\stdClass $r) => new ChildrenData(id: (int)$r->id, name: $r->name, slug: $r->slug, url: route('shop.category.view', $r->slug),),
                $categoriesRaw,
            );
        }

        $listing = $this->listingResolver->resolve($allProductIds, $params, $page, $perPage, $clientContext);

        $mainInfo->totalProducts = $listing['total'];

        $filters = $this->getCachedFilters(
            $mainInfo->id,
            array_map(fn(ChildrenData $cat) => $cat->id, $categories),
            $allProductIds,
            $params,
        );

        //Контент блоки
        $blocks = $this->blockRepository->getBlocksByContainer('promotion', $mainInfo->id);
        // вытаскиваем FAQ из блоков, если есть
        $faq = [];
        foreach ($blocks as $block) {
            if ($block->widget->slug == 'faq') {
                $faq = $block->widget->params['items'];
                break;
            }
        }

        $meta = $this->seoAdapter->getSeo('discount.promotion', $mainInfo, $page);
        $meta->ogSiteName = $web->web_name;
        $meta->canonical = route('shop.promotion.view', $slug);
        if (!is_null($mainInfo->image)) $meta->addImage(OgImage::fromData($mainInfo->image));

        $schema = $this->schemaBuilder->buildForProductIndex($listing['products'], $mainInfo->slug, 'promotion', $faq);

        return new ProductIndexPageData(
            mainInfo: $mainInfo,
            secondInfo: null,
            blocks: $blocks,
            products: $listing['products'],
            paginator: $listing['paginator'],
            filters: $filters,
            meta: $meta,
            schema: $schema,
        );
    }

    private function getCachedFilters(int $promotionId, array $categoryIds, array $productIds, array $params): FilterProductsData
    {
        $key_cache = str_replace('{id}', (string)$promotionId, CacheInvalidationRegistry::PROMOTION_FILTERS_ID);

        $aggregates = cache_remember(
            $key_cache,
            now()->addDay(),
            fn() => $this->filtersResolver->resolveAggregates($categoryIds, $productIds),
        );

        return $this->filtersResolver->buildFilterProductsData($aggregates, $params);
    }
}
