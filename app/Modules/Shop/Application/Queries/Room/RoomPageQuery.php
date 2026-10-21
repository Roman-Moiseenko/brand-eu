<?php

namespace App\Modules\Shop\Application\Queries\Room;

use App\Modules\Setting\Application\Actions\GetWebSettingsUseCase;
use App\Modules\Shop\Application\DTOs\ClientContext;
use App\Modules\Shop\Application\DTOs\Elements\ChildrenData;
use App\Modules\Shop\Application\DTOs\Elements\UrlData;
use App\Modules\Shop\Application\DTOs\Entities\CategoryRoomSecondData;
use App\Modules\Shop\Application\DTOs\PageElements\FilterProductsData;
use App\Modules\Shop\Application\DTOs\PageElements\OgImage;
use App\Modules\Shop\Application\DTOs\Pages\ProductIndexPageData;
use App\Modules\Shop\Application\Services\ProductFiltersResolver;
use App\Modules\Shop\Application\Services\ProductListingResolver;
use App\Modules\Shop\Infrastructure\Persistence\Builders\SchemaBuilder;
use App\Modules\Shop\Infrastructure\Persistence\CacheInvalidationRegistry;
use App\Modules\Shop\Infrastructure\Persistence\Query\ContentBlockQueryRepository;
use App\Modules\Shop\Infrastructure\Persistence\Query\RoomPageQueryRepository;
use App\Modules\Shop\Infrastructure\Persistence\SeoAdapter;

readonly class RoomPageQuery
{
    public function __construct(
        private RoomPageQueryRepository $repository,
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
        $mainInfo = $this->repository->getRoom($slug);
        if (is_null($mainInfo)) return null;

        $key_cache = str_replace('{id}', (string)$mainInfo->id, CacheInvalidationRegistry::ROOM_PRODUCTS_ID);

        $perPage = 20;
        $page = (int)($params['page'] ?? 1);

        /**
         * $allProductIds - Список всех ID товаров без фильтрации
         */
        $allProductIds = cache_remember(
            $key_cache,
            now()->addDay(),
            fn() => $this->repository->getProductIdsInRoom($mainInfo->id),
        ) ?? [];

        $categories = [];
        if ($allProductIds) {
            $categoriesRaw = $this->repository->getCategoriesByProductIds($allProductIds, $params);
            $categories = array_map(
                fn(\stdClass $r) => new ChildrenData(id: (int)$r->id, name: $r->name, slug: $r->slug),
                $categoriesRaw,
            );
        }

        $listing = $this->listingResolver->resolve($allProductIds, $params, $page, $perPage, $clientContext);

        $urlBack = $mainInfo->parent
            ? new UrlData(
                url: route('shop.room.view', $mainInfo->parent->slug),
                name: $mainInfo->parent->name,
            )
            : new UrlData(url: route('shop.room.index'), name: 'Комнаты');
        $mainInfo->back = $urlBack;
        $mainInfo->totalProducts = $listing['total'];

        $secondInfo = new CategoryRoomSecondData(
            children: $categories,
            back: new UrlData(url: route('shop.category.index'), name: 'По категориям'),
            entity: 'category',
        );

        $filters = $this->getCachedFilters(
            $mainInfo->id,
            array_map(fn(ChildrenData $cat) => $cat->id, $categories),
            $allProductIds,
            $params,
        );

        $blocks = $this->blockRepository->getBlocksByContainer('room', $mainInfo->id);
        // вытаскиваем FAQ из блоков, если есть
        $faq = [];
        foreach ($blocks as $block) {
            if ($block->widget->slug == 'faq') {
                $faq = $block->widget->params['items'];
                break;
            }
        }

        $meta = $this->seoAdapter->getSeo('catalog.room', $mainInfo, $page);
        $meta->ogSiteName = $web->web_name;
        $meta->canonical = route('shop.room.view', $slug);
        if (!is_null($mainInfo->image)) $meta->addImage(OgImage::fromData($mainInfo->image));

        $schema = $this->schemaBuilder->buildForProductIndex($listing['products'], $mainInfo->slug, 'room', $faq);

        return new ProductIndexPageData(
            mainInfo: $mainInfo,
            secondInfo: $secondInfo,
            blocks: $blocks,
            products: $listing['products'],
            paginator: $listing['paginator'],
            filters: $filters,
            meta: $meta,
            schema: $schema,
        );
    }

    private function getCachedFilters(int $roomId, array $categoryIds, array $productIds, array $params): FilterProductsData
    {
        $key_cache = str_replace('{id}', (string)$roomId, CacheInvalidationRegistry::ROOM_FILTERS_ID);

        $aggregates = cache_remember(
            $key_cache,
            now()->addDay(),
            fn() => $this->filtersResolver->resolveAggregates($categoryIds, $productIds),
        );

        return $this->filtersResolver->buildFilterProductsData($aggregates, $params);
    }
}
