<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Actions\ScraperCategory;

use App\Modules\Parser\Application\DTOs\ScraperCategory\ScraperCategoryResultData;
use Illuminate\Support\Facades\Log;

/**
 * Заглушка обработки товаров, полученных от парсера.
 * Реальная логика будет добавлена в следующих итерациях.
 */
readonly class ProcessScraperProductsUseCase
{
    public function execute(ScraperCategoryResultData $dto): void
    {
        Log::info('ScraperCategory: получены товары', [
            'brand' => $dto->brand,
            'id' => $dto->id,
            'products_count' => count($dto->products),
            'error' => $dto->error,
        ]);
    }
}
