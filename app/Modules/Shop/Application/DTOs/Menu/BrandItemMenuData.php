<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\DTOs\Menu;

use App\Modules\Shop\Application\DTOs\Elements\ImageInfoData;

readonly class BrandItemMenuData
{
    public function __construct(
        public int            $id,
        public string         $name,
        public string         $slug,
        public ?ImageInfoData $image = null,
    )
    {
    }
}
