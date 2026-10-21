<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\Queries\Category;

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
use App\Modules\Shop\Infrastructure\Persistence\Query\CategoryPageQueryRepository;
use App\Modules\Shop\Infrastructure\Persistence\Query\ContentBlockQueryRepository;
use App\Modules\Shop\Infrastructure\Persistence\SeoAdapter;

readonly class CategoryPageQuery
{
    public function __construct(
        private CategoryPageQueryRepository $repository,
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

        $mainInfo = $this->repository->getCategory($slug);
        if (is_null($mainInfo)) return null;

        $key_cache = str_replace('{id}', (string)$mainInfo->id, CacheInvalidationRegistry::CATEGORY_PRODUCTS_ID);

        $perPage = 20;
        $page = (int)($params['page'] ?? 1);

        //Список всех ID товаров без фильтрации
        $allProductIds = cache_remember(
            $key_cache,
            now()->addDay(),
            fn() => $this->repository->getProductIdsInCategory($mainInfo->id),
        ) ?? [];

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

        $listing = $this->listingResolver->resolve($allProductIds, $params, $page, $perPage, $clientContext);

        $mainInfo->back = $mainInfo->parent
            ? new UrlData(
                url: route('shop.category.view', $mainInfo->parent->slug),
                name: $mainInfo->parent->name,
            )
            : new UrlData(url: route('shop.category.index'), name: 'Каталог');
        $mainInfo->totalProducts = $listing['total'];

        $filters = $this->getCachedFilters($mainInfo->id, $allProductIds, $params);

        //Контент блоки
        $blocks = $this->blockRepository->getBlocksByContainer('category', $mainInfo->id);
        // вытаскиваем FAQ из блоков, если есть
        $faq = [];
        foreach ($blocks as $block) {
            if ($block->widget->slug == 'faq') {
                $faq = $block->widget->params['items'];
                break;
            }
        }

        //Мета-теги
        $meta = $this->seoAdapter->getSeo('catalog.category', $mainInfo, $page);
        $meta->ogSiteName = $web->web_name;
        $meta->canonical = route('shop.category.view', $slug);
        if (!is_null($mainInfo->image)) $meta->addImage(OgImage::fromData($mainInfo->image));

        $schema = $this->schemaBuilder->buildForProductIndex($listing['products'], $mainInfo->slug, 'category', $faq);

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

    private function getCachedFilters(int $categoryId, array $allProductIds, array $params): FilterProductsData
    {
        $key_cache = str_replace('{id}', (string)$categoryId, CacheInvalidationRegistry::CATEGORY_FILTERS_ID);

        $aggregates = cache_remember(
            $key_cache,
            now()->addDay(),
            fn() => $this->filtersResolver->resolveAggregates([$categoryId], $allProductIds),
        );

        return $this->filtersResolver->buildFilterProductsData($aggregates, $params);
    }
}
