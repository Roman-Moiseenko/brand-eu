<?php

namespace App\Modules\Parser\Application\Services;

use App\Modules\Parser\Application\DTOs\ParserCalculateData;

class CalculateParserPriceService
{

    /**
     * @param float $priceSell
     * @param int|null $currency - на будущее, когда будут не только злоты
     * @return void
     */
    public function execute(float $priceSell, ?int $currency = null): ParserCalculateData
    {
        $retail = (int)ceil($priceSell * 30);
        $minimal = (int)ceil($priceSell * 23);
        $cost = (int)ceil($priceSell * 23);

        return new ParserCalculateData($retail, $minimal, $cost);
    }
}
