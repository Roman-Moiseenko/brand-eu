<?php

namespace App\Modules\Parser\Infrastructure\Services\Babolat;

use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Application\Actions\Category\CreateParserCategoryUseCase;
use App\Modules\Parser\Application\DTOs\Category\ParserCategoryCreateData;

class CategoryParserService
{
    private const string BRAND_NAME = 'Babolat';

    public function __construct(
        private readonly BrandRepositoryInterface $brandRepository,
        private readonly CreateParserCategoryUseCase $createParserCategoryUseCase,
    ) {
    }

    public function add(array $categoryData, ?int $parentId = null): bool
    {
        $brand = $this->brandRepository->getByName(self::BRAND_NAME);

        $category = $this->createParserCategoryUseCase->execute(new ParserCategoryCreateData(
            name: $categoryData['name'],
            code: empty($categoryData['url']) ? $categoryData['name'] : $this->urlToSlug($categoryData['url']),
            brandId: $brand->id,
            parentId: $parentId,
            url: $categoryData['url'] ?? '',
        ));

        foreach ($categoryData['children'] ?? [] as $child) {
            $this->add($child, $category->id);
        }

        return true;
    }

    private function urlToSlug(?string $url): ?string
    {
        if ($url === null || $url === '' || $url === '#') {
            return null;
        }

        $parts = parse_url($url);
        $slug = trim($parts['path'] ?? '', '/');

        // добавляем значения GET-параметров через дефис
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);

            // стабильный порядок: при повторных запусках slug не «прыгает»
            ksort($query);

            foreach ($query as $value) {
                if (is_array($value)) {
                    $value = implode('-', $value);
                }
                $value = trim((string)$value, '/');
                if ($value !== '') {
                    $slug .= '-' . $value;
                }
            }
        }

        return $slug === '' ? null : $slug;
    }
}
