<?php

declare(strict_types=1);

namespace App\Modules\Parser\Domain\Entities;

final class ParserProductSizeEntity
{
    public ?int $id = null {
        get => $this->id;
        set => $this->id = $value;
    }

    public ?int $productId = null {
        get => $this->productId;
        set => $this->productId = $value;
    }

    public string $sizeOriginal = '' {
        get => $this->sizeOriginal;
        set => $this->sizeOriginal = $value;
    }

    public string $size = '' {
        get => $this->size;
        set => $this->size = $value;
    }

    public ?int $productSizeId = null {
        get => $this->productSizeId;
        set => $this->productSizeId = $value;
    }

    public bool $available = true {
        get => $this->available;
        set => $this->available = $value;
    }

    public string $url = '' {
        get => $this->url;
        set => $this->url = $value;
    }

    public string $sku = '' {
        get => $this->sku;
        set => $this->sku = $value;
    }

    public float $price = 0 {
        get => $this->price;
        set => $this->price = $value;
    }

    /**
     * Значения размера из связанного товарного размера ProductSize.
     * Заполняется репозиторием из связи productSize.
     *
     * @var array<int|string, mixed>
     */
    public array $values = [] {
        get => $this->values;
        set => $this->values = $value;
    }
}
