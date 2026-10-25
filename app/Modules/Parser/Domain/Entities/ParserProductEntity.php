<?php

namespace App\Modules\Parser\Domain\Entities;

use AllowDynamicProperties;

final class ParserProductEntity
{
    // ======================== Идентификатор ========================

    public ?int $id = null {
        get => $this->id;
        set => $this->id = $value;
    }

    public ?int $productId = null {
        get => $this->productId;
        set => $this->productId = $value;
    }

    public ?int $brandId = null {
        get => $this->brandId;
        set => $this->brandId = $value;
    }

    public ?int $categoryId = null {
        get => $this->categoryId;
        set => $this->categoryId = $value;
    }
    // ======================== Основные поля ========================

    public string $name {
        get => $this->name;
        set => $this->name = $value;
    }

    public string $url = '' {
        get => $this->url;
        set => $this->url = $value;
    }

    public string $code {
        get => $this->code;
        set => $this->code = $value;
    }

    public float $priceBase = 0 {
        get => $this->priceBase;
        set => $this->priceBase = $value;
    }

    public float $priceSell = 0 {
        get => $this->priceSell;
        set => $this->priceSell = $value;
    }

    /** id в магазине Бренда */
    public string $makerId = '' {
        get => $this->makerId;
        set => $this->makerId = $value;
    }

    /** чистый json с оригинальными данными */
    public string $data = '' {
        get => $this->data;
        set => $this->data = $value;
    }

    /** Можно или нет возить под заказ */
    public bool $availability = true {
        get => $this->availability;
        set => $this->availability = $value;
    }

    public ?\DateTimeImmutable $createdAt = null {
        get => $this->createdAt;
        set => $this->createdAt = $value;
    }

    public ?\DateTimeImmutable $updatedAt = null {
        get => $this->updatedAt;
        set => $this->updatedAt = $value;
    }

    public function __construct(
        string $name,
        string $code,
    )
    {
        $this->name = $name;
        $this->code = $code;
    }

    /** @var ParserProductSizeEntity[] */
    public array $sizes = [] {
        get => $this->sizes;
        set => $this->sizes = $value;
    }

    public function addSize(ParserProductSizeEntity $size): void
    {
        $this->sizes = [...$this->sizes, $size];
    }
}
