<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\DTOs\Menu;

use App\Modules\Shop\Application\DTOs\Elements\ImageInfoData;

class BrandItemMenuData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?ImageInfoData $image = null,
    )
    {
    }
}
