<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\DTOs\Pages;

use App\Modules\Shop\Application\DTOs\Entities\BrandData;
use App\Modules\Shop\Application\DTOs\PageElements\SeoData;
use App\Modules\Shop\Domain\Schema\SchemaData;

/**
 * Для страниц каталога - shop.catalog.category и shop.catalog.room
 */
readonly class BrandIndexPageData
{

    public function __construct(
        public SeoData $meta,
        /** @var BrandData[] $brands */
        public array   $brands,
        public SchemaData           $schema,
    )
    {
    }
}
