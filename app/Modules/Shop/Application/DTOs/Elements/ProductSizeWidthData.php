<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\DTOs\Elements;

/**
 * Группа размеров, сгруппированная по ширине (ось 'width') для двухосной размерной сетки.
 *
 * @see ProductSizeData
 */
readonly class ProductSizeWidthData
{
    /**
     * @param ProductSizeData[] $items
     */
    public function __construct(
        public string  $value,
        public ?string $label,
        public array   $items,
    )
    {
    }
}
