<?php

namespace App\Modules\Shop\Application\Brand;

use App\Modules\Setting\Application\Actions\GetWebSettingsUseCase;
use App\Modules\Shop\Application\DTOs\ClientContext;
use App\Modules\Shop\Application\DTOs\Elements\ChildrenData;
use App\Modules\Shop\Application\DTOs\Elements\UrlData;
use App\Modules\Shop\Application\DTOs\Entities\CategoryRoomMainData;
use App\Modules\Shop\Application\DTOs\Entities\CategoryRoomSecondData;
use App\Modules\Shop\Application\DTOs\PageElements\FilterProductsData;
use App\Modules\Shop\Application\DTOs\PageElements\OgImage;
use App\Modules\Shop\Application\DTOs\Pages\ProductIndexPageData;
use App\Modules\Shop\Application\Services\ProductFiltersResolver;
use App\Modules\Shop\Application\Services\ProductListingResolver;
use App\Modules\Shop\Infrastructure\Persistence\Builders\SchemaBuilder;
use App\Modules\Shop\Infrastructure\Persistence\CacheInvalidationRegistry;
use App\Modules\Shop\Infrastructure\Persistence\Query\BrandPageQueryRepository;
use App\Modules\Shop\Infrastructure\Persistence\Query\ContentBlockQueryRepository;
use App\Modules\Shop\Infrastructure\Persistence\Query\RoomPageQueryRepository;
use App\Modules\Shop\Infrastructure\Persistence\SeoAdapter;

readonly class BrandPageQuery
{
    public function __construct(
        private BrandPageQueryRepository $repository,
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
        $mainInfo = $this->repository->getBrand($slug);
        if (is_null($mainInfo)) return null;

        $key_cache = str_replace('{id}', (string)$mainInfo->id, CacheInvalidationRegistry::BRAND_PRODUCTS_ID);

        $perPage = 20;
        $page = (int)($params['page'] ?? 1);
        $allProductIds = cache_remember(
            $key_cache,
            now()->addDay(),
            fn() => $this->repository->getProductIdsInBrand($mainInfo->id),
        );

        // Категории товаров бренда — кладём в $mainInfo->children
        $categories = [];
        if ($allProductIds) {
            $categoriesRaw = $this->roomRepository->getCategoriesByProductIds($allProductIds, $params);
            $categories = array_map(
                fn(\stdClass $r) => new ChildrenData(id: (int)$r->id, name: $r->name, slug: $r->slug),
                $categoriesRaw,
            );
        }

        // Комнаты товаров бренда — кладём в $secondInfo
        $rooms = [];
        if ($allProductIds) {
            $roomsRaw = $this->repository->getRoomsByProductIds($allProductIds, $params);
            $rooms = array_map(
                fn(\stdClass $r) => new ChildrenData(id: (int)$r->id, name: $r->name, slug: $r->slug),
                $roomsRaw,
            );
        }
        $secondInfo = new CategoryRoomSecondData(
            children: $rooms,
            back: new UrlData(url: route('shop.room.index'), name: 'По комнатам'),
            entity: 'room',
        );

        $mainInfo = new CategoryRoomMainData(
            id: $mainInfo->id,
            name: $mainInfo->name,
            slug: $mainInfo->slug,
            children: $categories,
            entity: 'category',
            parent: null,
            totalProducts: 0,
            title: $mainInfo->title,
            description: $mainInfo->description,
            image: $mainInfo->image,
        );
        $listing = $this->listingResolver->resolve($allProductIds, $params, $page, $perPage, $clientContext);

        $mainInfo->totalProducts = $listing['total'];

        $filters = $this->getCachedFilters(
            $mainInfo->id,
            array_map(fn(ChildrenData $cat) => $cat->id, $categories),
            $allProductIds,
            $params,
        );

        //Контент блоки
        $blocks = $this->blockRepository->getBlocksByContainer('brand', $mainInfo->id);
        // вытаскиваем FAQ из блоков, если есть
        $faq = [];
        foreach ($blocks as $block) {
            if ($block->widget->slug == 'faq') {
                $faq = $block->widget->params['items'];
                break;
            }
        }

        $meta = $this->seoAdapter->getSeo('catalog.brand', $mainInfo, $page);
        $meta->ogSiteName = $web->web_name;
        $meta->canonical = route('shop.brand.view', $slug);
        if (!is_null($mainInfo->image)) $meta->addImage(OgImage::fromData($mainInfo->image));

        $schema = $this->schemaBuilder->buildForProductIndex($listing['products'], $mainInfo->slug, 'brand', $faq);

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

    private function getCachedFilters(int $brandId, array $categoryIds, array $productIds, array $params): FilterProductsData
    {
        $key_cache = str_replace('{id}', (string)$brandId, CacheInvalidationRegistry::BRAND_FILTERS_ID);

        $aggregates = cache_remember(
            $key_cache,
            now()->addDay(),
            fn() => $this->filtersResolver->resolveAggregates($categoryIds, $productIds),
        );

        return $this->filtersResolver->buildFilterProductsData($aggregates, $params);
    }
}
