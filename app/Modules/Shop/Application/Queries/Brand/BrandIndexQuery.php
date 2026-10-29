<?php

namespace App\Modules\Shop\Application\Queries\Brand;


use App\Modules\Setting\Application\Actions\GetWebSettingsUseCase;
use App\Modules\Shop\Application\DTOs\PageElements\SeoData;
use App\Modules\Shop\Application\DTOs\Pages\BrandIndexPageData;
use App\Modules\Shop\Infrastructure\Persistence\Builders\SchemaBuilder;
use App\Modules\Shop\Infrastructure\Persistence\Query\BrandPageQueryRepository;

readonly class BrandIndexQuery
{
    public function __construct(
        private BrandPageQueryRepository $repository,
        private GetWebSettingsUseCase $webSettingsUseCase,
        private SchemaBuilder               $schemaBuilder,
    )
    {
    }

    public function execute():BrandIndexPageData
    {
        $web = $this->webSettingsUseCase->execute();

        $brands = $this->repository->getBrands();

        $meta = new SeoData(
            title: $web->brand_title,
            description: $web->brand_desc,
            canonical: route('shop.brand.index'),
            ogSiteName: $web->web_name,
        );

        $schema = $this->schemaBuilder->buildForCategoryIndex($brands, 'brand');
        return new BrandIndexPageData(
            meta: $meta,
            brands: $brands,
            schema: $schema,
        );
    }
}
