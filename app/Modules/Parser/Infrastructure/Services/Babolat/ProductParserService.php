<?php

namespace App\Modules\Parser\Infrastructure\Services\Babolat;

use App\Modules\Base\Service\TranslateService;
use App\Modules\Catalog\Application\Actions\Product\EditPage\SetProductSizesUseCase;
use App\Modules\Catalog\Application\DTOs\Product\EditPage\UpdateProductSizesData;
use App\Modules\Catalog\Application\Services\AttachVariantAttributeService;
use App\Modules\Catalog\Domain\Entities\AttributeVariantEntity;
use App\Modules\Catalog\Domain\Entities\ProductEntity;
use App\Modules\Catalog\Domain\Entities\ProductSizeEntity;
use App\Modules\Catalog\Domain\Interfaces\AttributeRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\ProductSizeRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\SizeGridRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObjects\AttributeName;
use App\Modules\Catalog\Domain\ValueObjects\Code;
use App\Modules\Parser\Domain\Entities\ParserProductEntity;
use App\Modules\Parser\Domain\Entities\ParserProductSizeEntity;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserProductRepositoryInterface;
use App\Modules\Parser\Infrastructure\Jobs\LoadPublishedProductJob;
use App\Modules\Parser\Infrastructure\Mappers\SizeMapper;
use App\Modules\Parser\Infrastructure\Services\Common\CalculateParserPriceService;
use App\Modules\Parser\Infrastructure\Services\Common\PhotosProductService;
use App\Modules\Shared\Domain\ValueObjects\QueueName;
use App\Modules\Shared\Domain\ValueObjects\Slug;

readonly class ProductParserService
{
    public function __construct(
        private ParserProductRepositoryInterface  $parserProductRepository,
        private ParserCategoryRepositoryInterface $parserCategoryRepository,
        private ProductRepositoryInterface        $productRepository,
        private ProductSizeRepositoryInterface    $productSizeRepository,
        private SizeGridRepositoryInterface       $sizeGridRepository,
        private TranslateService                  $translateService,
        private SetProductSizesUseCase            $setProductSizesUseCase,
        private SizeMapper                        $sizeMapper,
        private CalculateParserPriceService       $calculate,
        private AttachVariantAttributeService     $attachVariantAttributeService,
        private AttributeRepositoryInterface      $attributeRepository,
        private PhotosProductService              $photosProductService,
    )
    {
    }

    /**
     * Создаёт ProductEntity и ParserProductEntity из спарсенных данных.
     */
    public function create(array $decoded, array $sizes, int $categoryId, $url): ?ProductEntity
    {
        //По артикулу проверяем наличие товара в базе и в размерной сетке
        $code = $decoded['attributes']['1']['reference']
            ?? $decoded['attributes']['2']['reference']
            ?? $decoded['reference']
            ?? null;

        if (!is_null($prod = $this->productRepository->findByCode($code))) return $prod;
        //Находим категорию парсера
        $category = $this->parserCategoryRepository->getById($categoryId);

        if ($category->categoryId === null) {
            \Log::warning('Пропуск товара: категория парсера не привязана к категории каталога', [
                'parser_category_id' => $categoryId,
                'product_name' => $decoded['name'] ?? null,
            ]);

            return null;
        }

        $name = $this->translateService->translate($decoded['name']);

        $color = '';
        //Получаем цвет
        if (isset($decoded['attributes']['2'])) {
            $attribute = $decoded['attributes']['2'];
            if ($attribute['group'] == 'Kolor') {
                $color = $this->translateService->translate($attribute['name']);
            }
        }
        //Проверяем цвет в названии товара, и по возможности добавляем
        $attributeColor = $this->attributeRepository->findByName(AttributeName::COLOR);
        $variants = array_map(function (AttributeVariantEntity $entity) {
            return $entity->name;
        }, $attributeColor->variants);
        if ($variants !== []) {
            $pattern = '/' . implode('|', array_map(
                fn (string $variant) => preg_quote($variant, '/'),
                $variants
            )) . '/iu';
            if (!preg_match($pattern, $name, $m)) {
                $name = $name . ', ' . $color;
            }
        }
        //Создаем Товар
        $product = new ProductEntity(
            name: $name,
            code: new Code($code),
            slug: new Slug($name),
            mainCategoryId: $category->categoryId,
            brandId: $category->brandId,
        );
        $product->description = $this->translateService->translate($decoded['description_short']);
        $product = $this->productRepository->save($product);
        //Устанавливаем цвет, как атрибут, если есть
        if (!empty($color)) $this->attachVariantAttributeService->execute($product->id, [$color], AttributeName::color());

        $sizesForParser = [];

        if (!is_null($category->sizeGridId)) {
            $dto = new UpdateProductSizesData($product->id, $category->sizeGridId);
            $this->setProductSizesUseCase->execute($dto);

            //Отключаем все размеры товара — по умолчанию все варианты сетки неактивны
            $grid = $this->sizeGridRepository->getById($category->sizeGridId);
            $desiredActive = [];
            foreach ($grid->getCombinations() as $values) {
                $desiredActive[ProductSizeEntity::keyFromValues($values)] = false;
            }

            $dimensionCode = $grid->dimensions[0]->code ?? 'size';
            $productSizes = $this->productSizeRepository->getByProductId($product->id);

            foreach ($sizes as $size) {
                $sizeProduct = $this->sizeMapper->map($size); //через Mapping получаем размер по нашей сетке
                //Находим размер товара и включаем его
                $sizeEntity = null;
                foreach ($productSizes as $productSize) {
                    if (($productSize->values[$dimensionCode] ?? null) === $sizeProduct) {
                        $sizeEntity = $productSize;
                        break;
                    }
                }
                if ($sizeEntity === null) continue;
                $desiredActive[$sizeEntity->key()] = true;
                $sizesForParser[] = ParserProductSizeEntity::create(
                   productSizeId: $sizeEntity->id,
                    sizeOriginal: $size,
                    size: $sizeProduct,
                );
            }
            //Применяем размерный ряд: включены только спарсенные размеры
            $this->productSizeRepository->rebuildForProduct($product->id, $grid, $desiredActive);
        }

        //Устанавливаем цену розницы и закупку
        $this->calculate->execute($decoded['price_without_reduction'], $product->id);

        //Загружаем Фото для товара
        $items = array_filter(array_map(function ($item) {
            if (isset($item['large']['url'])) return $item['large']['url'];
            return false;

        }, $decoded['images']));
        $this->photosProductService->execute($items, $product->id, $product->name);
        //После загрузки фото, публикуем товар
        LoadPublishedProductJob::dispatch($product->id)->onQueue(QueueName::PHOTO);

        //Создам Парсер-товар
        $parserProduct = new ParserProductEntity(
            name: $decoded['name'],
            code: $code
        );
        $parserProduct->brandId = $category->brandId;
        $parserProduct->url = $url;
        $parserProduct->categoryId = $categoryId;
        $parserProduct->priceSell = $decoded['price_without_reduction'] - $decoded['reduction'];
        $parserProduct->priceBase = $decoded['price_without_reduction'];
        $parserProduct->data = json_encode($decoded);
        $parserProduct->productId = $product->id;
        $parserProduct->makerId = $decoded['reference'];

        if (!is_null($category->sizeGridId)) {//Есть ли размерная сетка, добавляем $sizesForParser
            foreach ($sizesForParser as $sizeForParser) {
                $parserProduct->addSize($sizeForParser);
            }
        }
        $this->parserProductRepository->save($parserProduct);

        return $product;
    }

}
