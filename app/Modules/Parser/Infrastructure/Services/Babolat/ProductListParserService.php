<?php

namespace App\Modules\Parser\Infrastructure\Services\Babolat;

class ProductListParserService
{
    /**
     * Собирает ссылки на товары со всех страниц категории.
     *
     * @param string $categoryUrl
     * @param callable(string):array{0: array, 1: ?string} $parsePage
     *        возвращает [ссылки текущей страницы, url следующей страницы]
     * @return array
     */
    public function list(string $categoryUrl, callable $parsePage): array
    {
        [$links, $nextUrl] = $parsePage($categoryUrl);

        if ($nextUrl !== null) {
            $links = array_merge($links, $this->list($nextUrl, $parsePage));
        }

        return $links;
    }
}
