<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Services;

use App\Modules\Parser\Application\Actions\ScraperCategory\ProcessScraperProductsUseCase;
use App\Modules\Parser\Application\DTOs\ScraperCategory\ScraperCategoryResultData;
use App\Modules\Parser\Domain\Entities\ScraperCategoryEntity;
use App\Modules\Parser\Domain\Entities\ScraperProductEntity;
use App\Modules\Parser\Domain\Interfaces\ParserProductRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ScraperCategoryRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ScraperProductRepositoryInterface;

/**
 * Обрабатывает результат парсинга страницы категории.
 *
 * Если страница не последняя — дублирует текущую запись очереди,
 * заменяя url на next_page_url, после чего удаляет отработанную запись.
 * Полученные товары добавляет в очередь scraper_products.
 */
readonly class HandleScraperCategoryResultService
{
    public function __construct(
        private ScraperCategoryRepositoryInterface $scraperCategoryRepository,
        private ScraperProductRepositoryInterface $scraperProductRepository,
        private ParserProductRepositoryInterface $parserProductRepository,
        private ProcessScraperProductsUseCase $processScraperProductsUseCase,
    ) {}

    public function execute(ScraperCategoryResultData $dto): void
    {
        $current = $this->scraperCategoryRepository->getById($dto->id);

        if (!$dto->isLastPage) {
            $this->addNextPage($dto, $current);
        }

        $this->addProducts($dto, $current);

        $this->processScraperProductsUseCase->execute($dto);
    }

    private function addNextPage(ScraperCategoryResultData $dto, ScraperCategoryEntity $current): void
    {
        if ($dto->nextPageUrl === null) {
            return;
        }

        $duplicate = new ScraperCategoryEntity(
            parserCategoryId: $current->parserCategoryId,
            brandId: $current->brandId,
            url: $dto->nextPageUrl,
        );

        $this->scraperCategoryRepository->save($duplicate);
        $this->scraperCategoryRepository->delete($dto->id);
    }

    private function addProducts(ScraperCategoryResultData $dto, ScraperCategoryEntity $current): void
    {
        foreach ($dto->products as $product) {
            $sku = (string) ($product['sku'] ?? '');
            $url = (string) ($product['url'] ?? '');

            if ($sku === '' || $url === '') {
                continue;
            }

            // Если товар уже есть в parser_products (code = sku) — не добавляем в скрапер
            if ($this->parserProductRepository->existsByCode($sku)) {
                continue;
            }

            $this->scraperProductRepository->save(new ScraperProductEntity(
                parserCategoryId: $current->parserCategoryId,
                brandId: $current->brandId,
                url: $url,
            ));
        }
    }
}
