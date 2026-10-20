<?php

namespace App\Modules\Parser\Infrastructure\Services\Babolat;

use App\Modules\Accounting\Application\Actions\ProductPrice\SetPriceUseCase;
use App\Modules\Accounting\Application\DTOs\ProductPrice\SetProductPriceData;
use App\Modules\Accounting\Domain\ValueObjects\PriceType;
use App\Modules\Base\Service\TranslateService;
use App\Modules\Catalog\Application\Actions\Product\EditPage\SetProductSizesUseCase;
use App\Modules\Catalog\Application\DTOs\Product\EditPage\UpdateProductSizesData;
use App\Modules\Catalog\Domain\Entities\ProductEntity;
use App\Modules\Catalog\Domain\Entities\ProductSizeEntity;
use App\Modules\Catalog\Domain\Interfaces\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\ProductSizeRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\SizeGridRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObjects\Code;
use App\Modules\Parser\Application\Services\CalculateParserPriceService;
use App\Modules\Parser\Domain\Entities\ParserCategoryEntity;
use App\Modules\Parser\Domain\Entities\ParserProductEntity;
use App\Modules\Parser\Domain\Entities\ParserProductSizeEntity;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserProductRepositoryInterface;
use App\Modules\Parser\Infrastructure\Mappers\SizeMapper;
use App\Modules\Shared\Application\DTOs\JobPhotoLoadData;
use App\Modules\Shared\Domain\ValueObjects\PhotoType;
use App\Modules\Shared\Domain\ValueObjects\QueueName;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Infrastructure\Job\LoadPhotoByUrlJob;

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
        private SetPriceUseCase $setPriceUseCase,
    )
    {
    }

    /**
     * Создаёт ProductEntity и ParserProductEntity из спарсенных данных.
     */
    public function create(array $decoded, array $sizes, int $categoryId, $url): ProductEntity
    {
        //По артикулу проверяем наличие товара в базе и в размерной сетке
        $code = $decoded['reference'];

        if (!is_null($prod = $this->productRepository->findByCode($code))) return $prod;
        //Находим категорию парсера
        $category = $this->parserCategoryRepository->getById($categoryId);

        $name = $this->translateService->translate($decoded['name']);
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

                if ($sizeEntity === null) {
                    continue;
                }

                $desiredActive[$sizeEntity->key()] = true;

                $sizesForParser[] = [
                    'productSizeId' => $sizeEntity->id,
                    'sizeOriginal' => $size,
                    'size' => $sizeProduct,
                ];
            }

            //Применяем размерный ряд: включены только спарсенные размеры
            $this->productSizeRepository->rebuildForProduct($product->id, $grid, $desiredActive);
        }

        //Устанавливаем цену розницы и закупку
        $prices = $this->calculate->execute($decoded['price_without_reduction']);
        $dto = new SetProductPriceData($product->id, $prices->retail, PriceType::RETAIL);
        $this->setPriceUseCase->execute($dto);

        $dto = new SetProductPriceData($product->id, $prices->minimal, PriceType::MINIMAL);
        $this->setPriceUseCase->execute($dto);

        $dto = new SetProductPriceData($product->id, $prices->cost, PriceType::COST);
        $this->setPriceUseCase->execute($dto);


    //    \Log::info(json_encode($decoded['images']));
        //Загружаем Фото для товара
        $items = array_filter(array_map(function ($item) {
            if (isset($item['large']['url'])) return $item['large']['url'];
            return false;

        }, $decoded['images']));
        //\Log::info(json_encode($items));

       // dd($decoded['images']);
        foreach ($items as $imageItem) {
            $dtoPhoto = new JobPhotoLoadData(
                imageableId: $product->id,
                modelType: 'catalog.product',
                type: PhotoType::GALLERY,
                url: $imageItem,
                isProxy: false,
                alt: $product->name,
            );
            LoadPhotoByUrlJob::dispatch($dtoPhoto)->onQueue(QueueName::PHOTO);
        }


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

        if (!is_null($category->sizeGridId)) {
            //Есть ли размерная сетка ?

            //Добавляем размеры (ParserProductSize) в $parserProduct из $sizesForParser
            foreach ($sizesForParser as $sizeForParser) {
                $parserSize = new ParserProductSizeEntity();
                $parserSize->sizeOriginal = $sizeForParser['sizeOriginal'];
                $parserSize->size = $sizeForParser['size'];
                $parserSize->productSizeId = $sizeForParser['productSizeId'];
                $parserSize->available = true;

                $parserProduct->addSize($parserSize);
            }
        }

        $this->parserProductRepository->save($parserProduct);


        return $product;
    }

}
