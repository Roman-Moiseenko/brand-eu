<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Actions\ScraperProduct;

use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Application\DTOs\ScraperProduct\ScraperProductNextData;
use App\Modules\Parser\Application\DTOs\ScraperProduct\ScraperProductNextRequestData;
use App\Modules\Parser\Domain\Interfaces\ScraperProductRepositoryInterface;
use DomainException;

/**
 * Отдаёт парсеру следующий товар для обхода по бренду.
 */
readonly class GetNextScraperProductUseCase
{
    public function __construct(
        private ScraperProductRepositoryInterface $scraperProductRepository,
        private BrandRepositoryInterface $brandRepository,
    ) {}

    public function execute(ScraperProductNextRequestData $dto): ?ScraperProductNextData
    {
        $brand = $this->brandRepository->getByName($dto->brand);

        if ($brand === null) {
            throw new DomainException('Бренд не найден');
        }

        $first = $this->scraperProductRepository->findFirstByBrandId((int) $brand->id);

        if ($first === null) {
            return null;
        }

        return ScraperProductNextData::fromEntity($first);
    }
}
