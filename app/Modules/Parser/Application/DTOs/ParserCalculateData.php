<?php

namespace App\Modules\Parser\Application\DTOs;

class ParserCalculateData
{
    public function __construct(
        public int $retail,
        public int $minimal,
        public int $cost,
    )
    {

    }
}
