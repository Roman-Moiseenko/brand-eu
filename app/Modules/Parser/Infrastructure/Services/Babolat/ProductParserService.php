<?php

namespace App\Modules\Parser\Infrastructure\Services\Babolat;

use App\Modules\Base\Service\TranslateService;
use App\Modules\Catalog\Application\Actions\Product\EditPage\SetProductSizesUseCase;
use App\Modules\Catalog\Application\DTOs\Product\EditPage\UpdateProductSizesData;
use App\Modules\Catalog\Domain\Entities\ProductEntity;
use App\Modules\Catalog\Domain\Interfaces\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObjects\Code;
use App\Modules\Parser\Domain\Entities\ParserCategoryEntity;
use App\Modules\Parser\Domain\Entities\ParserProductEntity;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserProductRepositoryInterface;
use App\Modules\Parser\Infrastructure\Mappers\SizeMapper;
use App\Modules\Shared\Domain\ValueObjects\Slug;

readonly class ProductParserService
{
    public function __construct(
        private ParserProductRepositoryInterface  $parserProductRepository,
        private ParserCategoryRepositoryInterface $parserCategoryRepository,
        private ProductRepositoryInterface        $productRepository,
        private TranslateService                  $translateService,
        private SetProductSizesUseCase            $setProductSizesUseCase,
        private SizeMapper                        $sizeMapper,
    ) {
    }

    /**
     * Создаёт ProductEntity и ParserProductEntity из спарсенных данных.
     */
    public function create(array $decoded, array $sizes, int $categoryId, $productData): ?ProductEntity
    {

        //По артикулу проверяем наличие товара в базе и в размерной сетке
        $code = $decoded['reference'];
        if ($this->parserProductRepository->existsByCode($code)) return null;
        //Находим категорию парсера
        $category = $this->parserCategoryRepository->getById($categoryId);

        $name = $this->translateService->translate($decoded['name']);
        //Создаем Товар
        $product = new ProductEntity(
            name: $name,
            code: $code,
            slug: new Slug($name),
            mainCategoryId: $category->categoryId,
            brandId: $category->brandId,
        );
        $product->description = $this->translateService->translate($decoded['description_short']);

        $product = $this->productRepository->save($product);

        $sizesForParser = [];

        if (!is_null($category->sizeGridId)) {

            $dto = new UpdateProductSizesData($product->id, $category->sizeGridId);
            $this->setProductSizesUseCase->execute($dto);
            //TODO отключить все размеры в $product
            foreach ($sizes as $size) {

                $sizeProduct = $this->sizeMapper->map($size);
                //TODO Найти размер, через Mapping и включить его в $product и получить его id

                $sizesForParser[] = [
                    'productSizeId' => '< Здесь id размера>',
                    'sizeOriginal' => $size,
                ];
            }
            //Есть ли размерная сетка ?

            //TODO Получить товарную сетку и добавить в $product
        }


        //TODO Устанавливаем цену розницы и закупку

        //TODO Загружаем Фото для товара




        $parserProduct = new ParserProductEntity(
            name: $decoded['name'],
            code: $code
        );
        $parserProduct->brandId = $category->brandId;
        $parserProduct->url = $productData;
        $parserProduct->categoryId = $categoryId;

        $parserProduct->priceSell = $decoded['price_without_reduction'] - $decoded['reduction'];
        $parserProduct->priceBase = $decoded['price_without_reduction'];
        $parserProduct->data = json_encode($decoded);
        $parserProduct->productId = $product->id;

        if (!is_null($category->sizeGridId)) {
            //Есть ли размерная сетка ?

            //TODO Получить товарную сетку и добавить в $parserProduct

        }

        $parserProduct = $this->parserProductRepository->save($parserProduct);


        if (is_array($productData)) {

            //TODO Копируем из связываем $parserProduct и $product
            // для $product создаем модификацию
        }



        return $product;
    }

}
