<?php

namespace App\Modules\Parser\Infrastructure\Services\Common;

use App\Modules\Accounting\Application\Actions\ProductPrice\SetPriceUseCase;
use App\Modules\Accounting\Application\DTOs\ProductPrice\SetProductPriceData;
use App\Modules\Accounting\Domain\ValueObjects\PriceType;

readonly class CalculateParserPriceService
{

    public function __construct(
        private SetPriceUseCase $setPriceUseCase,
    )
    {

    }

    /**
     * @param float $priceSell
     * @param int $productId
     * @param int|null $currency - на будущее, когда будут не только злоты
     * @return void
     */
    public function execute(float $priceSell, int $productId, ?int $currency = null): void
    {
        $retail = (int)ceil($priceSell * 30);
        $minimal = (int)ceil($priceSell * 23);
        $cost = (int)ceil($priceSell * 23);


        $dto = new SetProductPriceData($productId, $retail, PriceType::RETAIL);
        $this->setPriceUseCase->execute($dto);

        $dto = new SetProductPriceData($productId, $minimal, PriceType::MINIMAL);
        $this->setPriceUseCase->execute($dto);

        $dto = new SetProductPriceData($productId, $cost, PriceType::COST);
        $this->setPriceUseCase->execute($dto);

    }
}
