<?php

namespace App\Modules\Parser\Infrastructure\Mappers;

class SizeMapper
{
    /**
     * @param string $size
     * @param int|null $sizeId - на будущее (id секти в которую надо конвертировать)
     * @return string
     */
    public function map(string $size, ?int $sizeId = null): string
    {

        // "9,5 / 44" → "44"; "XL" → "XL"
        if (str_contains($size, '/')) {
            $parts = explode('/', $size);
            $size = trim(end($parts));
        }

        //MAINDO Для Adidas подключить другие размерные сетки
        return $size;
    }
}
