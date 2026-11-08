<?php

namespace App\Modules\Parser\Infrastructure\Services;

use App\Modules\Base\Service\HttpPage;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Application\Actions\Category\CreateParserCategoryUseCase;
use App\Modules\Parser\Application\DTOs\Category\ParserCategoryCreateData;
use App\Modules\Parser\Application\Interfaces\ParserServiceInterface;
use App\Modules\Parser\Domain\Entities\ParserProductEntity;
use App\Modules\Parser\Domain\Entities\ScraperAvailableEntity;
use App\Modules\Parser\Domain\Entities\ScraperCategoryEntity;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserProductRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ScraperAvailableRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ScraperCategoryRepositoryInterface;

class AdidasParserService implements ParserServiceInterface
{
    private const string URL_CATEGORIES = 'https://adidas.pl';
    private const string BRAND_NAME = 'Adidas';
    private int $brandId;

    public function __construct(
        private readonly HttpPage $httpPage,
        private readonly BrandRepositoryInterface $brandRepository,
        private readonly CreateParserCategoryUseCase $createParserCategoryUseCase,
        private readonly ParserCategoryRepositoryInterface $parserCategoryRepository,
        private readonly ScraperCategoryRepositoryInterface $scraperCategoryRepository,
        private readonly ParserProductRepositoryInterface $parserProductRepository,
        private readonly ScraperAvailableRepositoryInterface $scraperAvailableRepository,
    )
    {

    }
    public function getListCategories(): array
    {
        return [];
        $page = $this->httpPage->getPage(self::URL_CATEGORIES,);

        dd($page);
        $pattern = '#<script data-mf-id="header-mf" data-mf-instance=":R4kkm:" data-mf-role="data" type="application\/json"[^>]*>(.*?)<\/script>#su';
        if (! preg_match($pattern, $page, $matches)) {
            throw new \Exception('Тег header-mf не найден на странице.');
        }
        $json = trim($matches[1]);

        // 3. Декодируем в ассоциативный массив
        $data = json_decode($json, true);
        $queries = $data['desktopData']['header'];

        return $queries;
    }

    public function addCategory($categoryData, ?int $parent_id = null): bool
    {
        return false;
        if (!isset($categoryData['url'])) return false;

        $brand = $this->brandRepository->getByName(self::BRAND_NAME);

        $dto = new ParserCategoryCreateData(
            name: $categoryData['title'],
            code: $categoryData['id'],
            brandId: $brand->id,
            parentId: $parent_id,
            url: $categoryData['url'],
        );
        $categoryEntity = $this->createParserCategoryUseCase->execute($dto);


        foreach ($categoryData['columns'] as $column) {
            $dto = new ParserCategoryCreateData(
                name: $column['topLink']['title'],
                code: $column['topLink']['id'],
                brandId: $brand->id,
                parentId: $categoryEntity->id,
                url: $column['topLink']['url'],
            );
            $subCategoryEntity = $this->createParserCategoryUseCase->execute($dto);

            foreach ($column['items'] as $item) {

                $dto = new ParserCategoryCreateData(
                    name: $item['title'],
                    code: $item['id'],
                    brandId: $brand->id,
                    parentId: $subCategoryEntity->id,
                    url: $item['url'],
                );
                $this->createParserCategoryUseCase->execute($dto);
            }
        }
        return true;
    }

    public function GetListProductsByCategory(string $categoryUrl): array
    {
        return [];
        // TODO: Implement GetListProductsByCategory() method.
    }

    public function GetParserProduct($productData, $categoryId)
    {
        return;
        // TODO: Implement GetParserProduct() method.
    }

    public function GetAvailability($url, $parserProductId): void
    {
        return;
        // TODO: Implement GetAvailability() method.
    }

    public function ScraperCategories(): void
    {
        $brand = $this->brandRepository->getByName(self::BRAND_NAME);

        if ($brand === null) {
            return;
        }

        // Если по бренду уже есть хотя бы одна запись в очереди — ничего не добавляем
        if ($this->scraperCategoryRepository->existsByBrandId((int) $brand->id)) {
            return;
        }

        foreach ($this->parserCategoryRepository->getActiveLeaves((int) $brand->id) as $category) {
            if ($category->url === '') {
                continue;
            }

            $this->scraperCategoryRepository->save(new ScraperCategoryEntity(
                parserCategoryId: (int) $category->id,
                brandId: (int) $brand->id,
                url: $category->url,
            ));
        }
    }

    public function ScraperAvailable(): void
    {
        $brand = $this->brandRepository->getByName(self::BRAND_NAME);

        if ($brand === null) {
            return;
        }

        // Если по бренду уже есть хотя бы одна запись — ничего не добавляем
        if ($this->scraperAvailableRepository->existsByBrandId((int) $brand->id)) {
            return;
        }

        foreach ($this->parserProductRepository->getAvailableByBrandId((int) $brand->id) as $parserProduct) {
            if ($parserProduct->url === '' || $parserProduct->categoryId === null) {
                continue;
            }

            $this->scraperAvailableRepository->save(new ScraperAvailableEntity(
                parserCategoryId: (int) $parserProduct->categoryId,
                brandId: (int) $brand->id,
                url: $parserProduct->url,
            ));
        }
    }
}
