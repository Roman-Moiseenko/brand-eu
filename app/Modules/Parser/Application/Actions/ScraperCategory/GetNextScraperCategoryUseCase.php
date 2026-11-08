<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Actions\ScraperCategory;

use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Application\DTOs\ScraperCategory\ScraperCategoryNextData;
use App\Modules\Parser\Application\DTOs\ScraperCategory\ScraperCategoryNextRequestData;
use App\Modules\Parser\Domain\Interfaces\ScraperCategoryRepositoryInterface;
use DomainException;

/**
 * Отдаёт парсеру следующую категорию для обхода по бренду.
 *
 * Только читает очередь scraper_categories. Наполнение очереди выполняется
 * в AdidasParserService::GetListProductsByCategory.
 */
readonly class GetNextScraperCategoryUseCase
{
    public function __construct(
        private ScraperCategoryRepositoryInterface $scraperCategoryRepository,
        private BrandRepositoryInterface $brandRepository,
    ) {}

    public function execute(ScraperCategoryNextRequestData $dto): ?ScraperCategoryNextData
    {
        $brand = $this->brandRepository->getByName($dto->brand);

        if ($brand === null) {
            throw new DomainException('Бренд не найден');
        }

        $first = $this->scraperCategoryRepository->findFirstByBrandId((int) $brand->id);

        if ($first === null) {
            return null;
        }

        return ScraperCategoryNextData::fromEntity($first);
    }
}
