<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Actions\ScraperProduct;

use App\Modules\Parser\Domain\Interfaces\ScraperProductRepositoryInterface;

readonly class RemoveScraperProductUseCase
{
    public function __construct(
        private ScraperProductRepositoryInterface $scraperProductRepository,
    ) {}

    public function execute(int $id): void
    {
        $this->scraperProductRepository->delete($id);
    }
}
