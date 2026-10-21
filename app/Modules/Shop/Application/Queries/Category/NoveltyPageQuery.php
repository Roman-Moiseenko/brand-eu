<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\Queries\Category;

use App\Modules\Setting\Application\Actions\GetWebSettingsUseCase;
use App\Modules\Shop\Application\DTOs\ClientContext;
use App\Modules\Shop\Application\DTOs\Elements\ChildrenData;
use App\Modules\Shop\Application\DTOs\Elements\UrlData;
use App\Modules\Shop\Application\DTOs\Entities\CategoryRoomMainData;
use App\Modules\Shop\Application\DTOs\Entities\CategoryRoomSecondData;
use App\Modules\Shop\Application\DTOs\PageElements\FilterProductsData;
use App\Modules\Shop\Application\DTOs\PageElements\SeoData;
use App\Modules\Shop\Application\DTOs\Pages\ProductIndexPageData;
use App\Modules\Shop\Application\Services\ProductListingResolver;
use App\Modules\Shop\Infrastructure\Persistence\Builders\SchemaBuilder;
use App\Modules\Shop\Infrastructure\Persistence\Query\CategoryPageQueryRepository;

readonly class NoveltyPageQuery
{
    public function __construct(
        private CategoryPageQueryRepository $repository,
        private ProductListingResolver $listingResolver,
        private SchemaBuilder $schemaBuilder,
        private GetWebSettingsUseCase $webSettingsUseCase,
    )
    {
    }

    public function execute(array $params, ClientContext $clientContext): ProductIndexPageData
    {
        $web = $this->webSettingsUseCase->execute();

        $perPage = 20;
        $page = (int)($params['page'] ?? 1);
        $allProductIds = $this->repository->getNewProductIds();

        $listing = $this->listingResolver->resolve($allProductIds, $params, $page, $perPage, $clientContext);

        $mainInfo = new CategoryRoomMainData(
            id: 0,
            name: 'Новинки',
            slug: 'novelty',
            children: [],
            entity: 'category',
            back: new UrlData(url: route('shop.category.index'), name: 'Каталог'),
            parent: null,
            totalProducts: $listing['total'],
        );

        $rooms = [];
        if ($listing['pageIds']) {
            $roomsRaw = $this->repository->getRoomsByProductIds($listing['pageIds'], $params);
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

        $filters = new FilterProductsData(
            minPrice: 0,
            maxPrice: 0,
            attributes: [],
            brands: [],
            tags: [],
            sortOrder: $params['order'] ?? '',
            tagId: isset($params['tag_id']) ? (int)$params['tag_id'] : null,
        );

        $meta = new SeoData('Новинки', 'Новинки Икеа оригинал из Европы с доставкой по всей России. IKEA доступные цены! В наличии в интернет магазине NORDI HOME');
        $meta->ogSiteName = $web->web_name;
        $meta->canonical = route('shop.novelty');

        $schema = $this->schemaBuilder->createSchema();

        return new ProductIndexPageData(
            mainInfo: $mainInfo,
            secondInfo: $secondInfo,
            blocks: [],
            products: $listing['products'],
            paginator: $listing['paginator'],
            filters: $filters,
            meta: $meta,
            schema: $schema,
        );
    }
}
