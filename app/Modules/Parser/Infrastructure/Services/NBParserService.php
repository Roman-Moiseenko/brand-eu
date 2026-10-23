<?php

namespace App\Modules\Parser\Infrastructure\Services;

use App\Modules\Base\Service\HttpPage;
use App\Modules\Base\Service\TranslateService;
use App\Modules\Catalog\Application\Actions\Series\FindOrCreateSeriesUseCase;
use App\Modules\Catalog\Domain\Entities\ProductEntity;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Application\Actions\Category\CreateParserCategoryUseCase;
use App\Modules\Parser\Application\DTOs\Category\ParserCategoryCreateData;
use App\Modules\Parser\Application\Interfaces\ParserServiceInterface;
use App\Modules\Parser\Domain\Entities\ParserProductEntity;
use App\Modules\Parser\Infrastructure\Services\NB\ProductParserService;
use Illuminate\Support\Facades\Log;

class NBParserService implements ParserServiceInterface
{
        public const string URL_CATEGORIES = 'https://newbalance.pl';
        private const string BRAND_NAME = 'New Balance';

        private int $brandId;
    public function __construct(
        private readonly HttpPage $httpPage,
        private readonly BrandRepositoryInterface $brandRepository,
        private readonly CreateParserCategoryUseCase $createParserCategoryUseCase,
        private readonly ProductParserService $productParserService,
        private readonly FindOrCreateSeriesUseCase $findOrCreateSeriesUseCase,
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

    public function GetListProductsByCategory(string $categoryUrl): array
    {
        $url = self::URL_CATEGORIES . '/' . $categoryUrl;

        $dataProducts = $this->parserProductsByUrl($url, true);
        return array_map(function ($product) {
            return $product['niceUrl'];
        }, $dataProducts);
    }

    private function parserProductsByUrl(string $url, bool $is_first_page = true)
    {
        $data = $this->httpPage->getPage($url, '', false);
        $queries = $this->getQueries($data);
        $data = [];
        foreach ($queries as $query) {
            if (isset($query['state']['data']['products']))
                $data = $query['state']['data']['products'];
        }
        $products = $data['items'];
        if (!$is_first_page) return $products; //Уже обрабатывается пагинация
        $pagination = $data['pagination']['lastPage'];
        for ($i = 2; $i <= $pagination; $i++) {
            $products = array_merge(
                $products,
                $this->parserProductsByUrl($url . '?page=' . $i, false)
            );
        }

        return $products;
    }
    private function getQueries(string $data)
    {
        $begin = strpos($data, '<script id="__NEXT_DATA__" type="application/json">');
        $text = substr($data, $begin + strlen('<script id="__NEXT_DATA__" type="application/json">'));
        $end = strpos($text, '</script>');
        $newData = substr($text, 0, $end);
        $array = json_decode($newData, true);

        try {
            return $array['props']['pageProps']['dehydratedState']['queries'];
        } catch (\Throwable $e) {
            Log::info(json_encode($array['props']['pageProps']));
            return [];
        }

    }


    public function GetParserProduct($productData, $categoryId):? ProductEntity
    {

        $url = self::URL_CATEGORIES . '/' . $productData;
        $data = $this->httpPage->getPage($url, '', false);
        $queries = $this->getQueries($data);
        $decoded = [];
        foreach ($queries as $query) {
            if (isset($query['state']['data']['product']))
                $decoded = $query['state']['data']['product'];
        }
        if (empty($decoded)) return null;

        return $this->productParserService->create($decoded, $categoryId, $url);
    }

    public function GetAvailability($productCate): void
    {
        // TODO: Implement GetAvailability() method.
    }

    public function GetSeries(): void
    {
        $data = $this->httpPage->getPage('https://newbalance.pl/meskie', '', false);
        $queries = $this->getQueries($data);
        foreach ($queries as $query) {
            if (isset($query['state']['data']['products']))
                $data = $query['state']['data']['products'];
        }
        $filters = $data['filters'];

        $series = [];
        foreach ($filters as $filter) {
            if ($filter['name'] == 'Seria') {
                $series = array_map(function ($item) {
                    return $item['value'];
                }, $filter['attributes']);
                break;
            }
        }

        foreach ($series as $serie) {
            $this->findOrCreateSeriesUseCase->execute($serie);
        }

    }
}
