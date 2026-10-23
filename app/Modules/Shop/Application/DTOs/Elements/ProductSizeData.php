<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\DTOs\Elements;

/**
 * Конкретный доступный размер товара (вариант products_sizes с is_active = true).
 */
readonly class ProductSizeData
{
    public function __construct(
        public int    $productSizeId,
        public string $value,
    )
    {
    }
}
