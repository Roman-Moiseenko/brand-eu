<?php

namespace App\Modules\Parser\Infrastructure\Services;

use App\Modules\Base\Service\HttpPage;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Application\Actions\Category\CreateParserCategoryUseCase;
use App\Modules\Parser\Application\DTOs\Category\ParserCategoryCreateData;
use App\Modules\Parser\Application\Interfaces\ParserServiceInterface;
use App\Modules\Parser\Domain\Entities\ParserProductEntity;

class NBParserService implements ParserServiceInterface
{
        private const string URL_CATEGORIES = 'https://newbalance.pl';
        private const string BRAND_NAME = 'New Balance';

        private int $brandId;
    public function __construct(
        private readonly HttpPage $httpPage,
        private readonly BrandRepositoryInterface $brandRepository,
        private readonly CreateParserCategoryUseCase $createParserCategoryUseCase,
    )
    {

    }
    public function getListCategories(): array
    {
        $page = $this->httpPage->getPage(self::URL_CATEGORIES, '', true, false);
        $pattern = '/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s';
        if (! preg_match($pattern, $page, $matches)) {
            throw new \Exception('Тег __NEXT_DATA__ не найден на странице.');
        }

        $json = trim($matches[1]);

        // 3. Декодируем в ассоциативный массив
        $data = json_decode($json, true);

        $queries =  $data['props']['pageProps']['dehydratedState']['queries'];
        foreach ($queries as $query) {
            if (isset($query['state']['data']['menu'])) {
                $menu = $query['state']['data']['menu']['children'];

                return $menu;
            }

        }
        return [];
    }

    public function addCategory($categoryData, ?int $parent_id = null): bool
    {
        $brand = $this->brandRepository->getByName(self::BRAND_NAME);
        //if (!isset($categoryData['kind'])) dd($categoryData);
        if ($categoryData['kind'] != 'product_category') return false;

        $dto = new ParserCategoryCreateData(
            name: $categoryData['title'],
            code: $categoryData['niceUrl'],
            brandId: $brand->id,
            parentId: $parent_id,
            url: $categoryData['niceUrl'],
        );

        $categoryEntity = $this->createParserCategoryUseCase->execute($dto);

        if (!empty($categoryData['children'])) {
            foreach ($categoryData['children'] as $child) {
                $this->addCategory($child, $categoryEntity->id);
            }
        }
        return true;
    }

    public function GetListProductsByCategory(string $categoryCode): array
    {
        // TODO: Implement GetListProductsByCategory() method.
    }

    public function GetParserProduct($productData): ?ParserProductEntity
    {
        // TODO: Implement GetParserProduct() method.
    }

    public function GetAvailability($productCate): void
    {
        // TODO: Implement GetAvailability() method.
    }
}
