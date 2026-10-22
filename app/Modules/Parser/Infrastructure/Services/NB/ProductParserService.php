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
use App\Modules\Parser\Application\Services\CalculateParserPriceService;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserProductRepositoryInterface;
use App\Modules\Parser\Infrastructure\Mappers\SizeMapper;
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
    )
    {
    }

    public function create(array $decoded, int $categoryId, $url): ?ProductEntity
    {
        //Считываем параметры
        $properties = $decoded['properties'];
        $model = ''; $for = []; $material = []; $gender = []; $color = '';

        $purpose = ''; $technology = ''; $weight = ''; $foot_type = ''; $Drop = '';

        foreach ($properties as $property) {
            if ($property['symbol'] == 'model') $model = $property['value']['dataList'][0]; //Модель
            if ($property['symbol'] == 'sex') $for = array_map(function ($item) { //Для кого
                return $this->translateService->translate($item);
            }, $property['value']['dataList']);
            if ($property['symbol'] == 'material') $material = array_map(function ($item) { //Материал
                return $this->translateService->translate($item);
            }, $property['value']['dataList']);

            if ($property['symbol'] == 'color_detailed') //Цвет
                $color = $this->translateService->translate($property['value']['dataList'][0]);


            //TODO Добавить остальные, проверить на Одежде и спортивных
            //Для спортивных моделей
            if ($property['symbol'] == 'purpose' && !is_null($property['value']['dataList'])) //Поверхность
                $purpose = $this->translateService->translate($property['value']['dataList'][0]);
            if ($property['symbol'] == 'technology' && !is_null($property['value']['dataList'])) //Поверхность
                $technology = $property['value']['dataList'][0];
            if ($property['symbol'] == 'weight' && !is_null($property['value']['dataList'])) //Поверхность
                $weight = $property['value']['dataList'][0];
            if ($property['symbol'] == 'foot_type' && !is_null($property['value']['dataList'])) //Поверхность
                $foot_type = $property['value']['dataList'][0];
            if ($property['name'] == 'Drop' && !is_null($property['value']['dataList'])) //Поверхность
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
        $product->description = $this->translateService->translate($decoded['description']);
        $product = $this->productRepository->save($product);
        //Устанавливаем цвет, как атрибут, если есть
        if (!empty($color)) $this->attachVariantAttributeService->execute($product->id, [$color], AttributeName::color());

        //Размерная сетка
        if (!is_null($category->sizeGridId)) {
            $dto = new UpdateProductSizesData($product->id, $category->sizeGridId);
            $this->setProductSizesUseCase->execute($dto);

            //Отключаем все размеры товара — по умолчанию все варианты сетки неактивны
            $grid = $this->sizeGridRepository->getById($category->sizeGridId);
            $desiredActive = [];
            foreach ($grid->getCombinations() as $values) {
                $desiredActive[ProductSizeEntity::keyFromValues($values)] = false;
            }







        }


    }
}
