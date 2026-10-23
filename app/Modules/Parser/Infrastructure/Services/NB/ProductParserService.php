<?php

namespace App\Modules\Parser\Infrastructure\Services\NB;

use App\Modules\Accounting\Application\Actions\ProductPrice\SetPriceUseCase;
use App\Modules\Base\Service\TranslateService;
use App\Modules\Catalog\Application\Actions\Product\EditPage\SetProductSizesUseCase;
use App\Modules\Catalog\Application\DTOs\Product\EditPage\UpdateProductSizesData;
use App\Modules\Catalog\Application\Services\AttachVariantAttributeService;
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
use App\Modules\Parser\Infrastructure\Services\NBParserService;
use App\Modules\Shared\Domain\ValueObjects\QueueName;
use App\Modules\Shared\Domain\ValueObjects\Slug;

class ProductParserService
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
        private SetPriceUseCase                   $setPriceUseCase,
        private AttachVariantAttributeService     $attachVariantAttributeService,
        private AttributeRepositoryInterface      $attributeRepository,
        private PhotosProductService              $photosProductService,
    )
    {
    }

    public function create(array $decoded, int $categoryId, $url): ?ProductEntity
    {
        //Считываем параметры
        $properties = $decoded['properties'];
        $model = '';
        $whom = [];
        $material = [];
        $gender = [];
        $color = '';

        $purpose = '';
        $technology = '';
        $weight = '';
        $foot_type = '';
        $Drop = '';

        foreach ($properties as $property) {
            if ($property['symbol'] == 'model') $model = $property['value']['dataList'][0]; //Модель
            if ($property['symbol'] == 'sex') $whom = array_map(function ($item) { //Для кого
                return $this->translateService->translate($item);
            }, $property['value']['dataList']);
            if ($property['symbol'] == 'material') $material = array_map(function ($item) { //Материал
                return $this->translateService->translate($item);
            }, $property['value']['dataList']);

            if ($property['symbol'] == 'color_detailed') //Цвет
                $color = $this->translateService->translate($property['value']['dataList'][0]);

            //Для спортивных моделей
            if ($property['symbol'] == 'purpose' && !is_null($property['value']['dataList'])) //Поверхность
                $purpose = $this->translateService->translate($property['value']['dataList'][0]);
            if ($property['symbol'] == 'technology' && !is_null($property['value']['dataList'])) //Технология
                $technology = $property['value']['dataList'][0];
            if ($property['symbol'] == 'weight' && !is_null($property['value']['dataList'])) //Вес
                $weight = $property['value']['dataList'][0];
            if ($property['symbol'] == 'foot_type' && !is_null($property['value']['dataList'])) //Тип стопы
                $foot_type = $property['value']['dataList'][0];
            if ($property['name'] == 'Drop' && !is_null($property['value']['dataList'])) //Подъем
                $Drop = $property['value']['dataList'][0];
        }
        if (empty($model)) return null;

        if (!is_null($prod = $this->productRepository->findByCode($model))) return $prod;
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

        //Создаем Товар
        $product = new ProductEntity(
            name: $name,
            code: new Code($model),
            slug: new Slug($name),
            mainCategoryId: $category->categoryId,
            brandId: $category->brandId,
        );

        $description = $this->stripAnchors($decoded['description']);

        $product->description = $this->translateService->translate($description);
        $product = $this->productRepository->save($product);
        //Устанавливаем цвет, как атрибут, если есть
        if (!empty($color)) $this->attachVariantAttributeService->execute($product->id, [$color], AttributeName::color());
        if (!empty($material)) $this->attachVariantAttributeService->execute($product->id, $material, AttributeName::material());

        if (!empty($whom)) $this->attachVariantAttributeService->execute($product->id, $whom, AttributeName::whom());

        //Размерная сетка
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

            $sizeDimensionCode = $grid->dimensions[0]->code ?? 'size';
            $hasWidth = $grid->hasDimension('width');
            $productSizes = $this->productSizeRepository->getByProductId($product->id);

            foreach ($decoded['variants'] ?? [] as $variant) {
                $buyable = (bool)($variant['availability']['buyable'] ?? false);
                //Размер и ширина из options
                $size = null;
                $width = null;
                foreach ($variant['options'] ?? [] as $option) {
                    if (($option['name'] ?? '') === 'Rozmiar' || ($option['groupId'] ?? '') === '32') {
                        $size = $option['value'];
                    }
                    if (($option['name'] ?? '') === 'Szerokość' || ($option['groupId'] ?? '') === '46') {
                        $width = $this->mapWidth($option['value']);
                    }
                }
                if ($size === null) continue;
                //Маппим размер под нашу сетку
                $sizeProduct = $this->sizeMapper->map($size);
                //Комбинация осей (size + width) для поиска варианта товара
                $values = [$sizeDimensionCode => $sizeProduct];
                if ($hasWidth && $width !== null) $values['width'] = $width;

                $sizeEntity = null;
                $key = ProductSizeEntity::keyFromValues($values);
                foreach ($productSizes as $productSize) {
                    if ($productSize->key() === $key) {
                        $sizeEntity = $productSize;
                        break;
                    }
                }
                if ($sizeEntity === null) continue;
                //Включаем размер в размерный ряд только если вариант доступен
                if ($buyable) $desiredActive[$key] = true;
                $sizesForParser[] = ParserProductSizeEntity::create(
                    productSizeId: $sizeEntity->id,
                    sizeOriginal: $size,
                    size: $sizeProduct,
                    sku: $variant['warehouseSymbol'] ?? '',
                    price: (float)($variant['prices']['basePrice']['gross'] ?? 0),
                    available: $buyable,
                );
            }

            //Применяем размерный ряд: включены только доступные спарсенные размеры
            $this->productSizeRepository->rebuildForProduct($product->id, $grid, $desiredActive);
        }

        //Устанавливаем цену розницы и закупку
        $this->calculate->execute($decoded['prices']['basePrice']['gross'], $product->id);
        //Загружаем Фото для товара
        $items =
            collect($decoded['picturesCategories'])
                ->where('slug', 'newbalance')
                ->flatMap(fn($category) => $category['pictures'])
                ->pluck('filename')
                ->map(fn($filename) => str_replace('{imageSafeUri}', NBParserService::URL_CATEGORIES . '/picture', $filename))
                ->values()
                ->all();
        $this->photosProductService->execute($items, $product->id, $product->name);

        //После загрузки фото, публикуем товар
        LoadPublishedProductJob::dispatch($product->id)->onQueue(QueueName::PHOTO);
        //Создам Парсер-товар
        $parserProduct = new ParserProductEntity(
            name: $decoded['name'],
            code: $model
        );
        $parserProduct->brandId = $category->brandId;
        $parserProduct->url = $url;
        $parserProduct->categoryId = $categoryId;
        $parserProduct->priceSell = $decoded['prices']['sellPrice']['gross'];
        $parserProduct->priceBase = $decoded['prices']['basePrice']['gross'];
        $parserProduct->data = json_encode($decoded);
        $parserProduct->productId = $product->id;
        $parserProduct->makerId = $decoded['id'];

        if (!is_null($category->sizeGridId)) {//Есть ли размерная сетка, добавляем $sizesForParser
            foreach ($sizesForParser as $sizeForParser) {
                $parserProduct->addSize($sizeForParser);
            }
        }
        $this->parserProductRepository->save($parserProduct);

        return $product;
    }


    /**
     * Преобразует полноту (ширину) из данных NB в значение оси 'width' размерной сетки:
     * 'Standardowa (D)' → 'D', 'Standardowa (B)' → 'B', 'Szeroka' → '2E'.
     */
    private function mapWidth(string $width): string
    {
        if (str_contains($width, '(D)')) {
            return 'D';
        }
        if (str_contains($width, '(B)')) {
            return 'B';
        }
        if (str_contains($width, '(2E)') || $width === 'Szeroka') {
            return '2E';
        }

        return $width;
    }

    function stripAnchors(string $html): string
    {
        return preg_replace('/<\/?a\b[^>]*>/i', '', $html);
    }
}
