<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Actions\ScraperAvailable;

use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Application\DTOs\ScraperAvailable\ScraperAvailableNextData;
use App\Modules\Parser\Application\DTOs\ScraperAvailable\ScraperAvailableNextRequestData;
use App\Modules\Parser\Domain\Interfaces\ScraperAvailableRepositoryInterface;
use DomainException;

/**
 * Отдаёт парсеру следующий товар для проверки доступности по бренду.
 */
readonly class GetNextScraperAvailableUseCase
{
    public function __construct(
        private ScraperAvailableRepositoryInterface $scraperAvailableRepository,
        private BrandRepositoryInterface $brandRepository,
    ) {}

    public function execute(ScraperAvailableNextRequestData $dto): ?ScraperAvailableNextData
    {
        $brand = $this->brandRepository->getByName($dto->brand);

        if ($brand === null) {
            throw new DomainException('Бренд не найден');
        }

        $first = $this->scraperAvailableRepository->findFirstByBrandId((int) $brand->id);

        if ($first === null) {
            return null;
        }

        return ScraperAvailableNextData::fromEntity($first);
    }
}
