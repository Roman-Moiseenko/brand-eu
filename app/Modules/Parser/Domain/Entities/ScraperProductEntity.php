<?php

declare(strict_types=1);

namespace App\Modules\Parser\Domain\Entities;

/**
 * Очередь товаров для парсинга (scraper_products).
 *
 * $parserCategoryId — ссылка на parser_categories, по которой в дальнейшем
 *     можно получить связанные category_id и size_grid_id из модуля Catalog.
 * $brandId — бренд, для которого ведётся обход товаров.
 * $url — адрес товара, который отдаётся парсеру.
 * $id — идентификатор записи для обратного взаимодействия.
 */
final class ScraperProductEntity
{
    public ?int $id = null {
        get => $this->id;
        set => $this->id = $value;
    }

    public int $parserCategoryId {
        get => $this->parserCategoryId;
        set => $this->parserCategoryId = $value;
    }

    public int $brandId {
        get => $this->brandId;
        set => $this->brandId = $value;
    }

    public string $url {
        get => $this->url;
        set => $this->url = $value;
    }

    public function __construct(
        int $parserCategoryId,
        int $brandId,
        string $url,
    ) {
        $this->parserCategoryId = $parserCategoryId;
        $this->brandId = $brandId;
        $this->url = $url;
    }
}
