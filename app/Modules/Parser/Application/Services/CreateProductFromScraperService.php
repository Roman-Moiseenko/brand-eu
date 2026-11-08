<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Services;

use App\Modules\Base\Service\TranslateService;
use App\Modules\Catalog\Application\Actions\Product\EditPage\SetProductSizesUseCase;
use App\Modules\Catalog\Application\Actions\Series\FindOrCreateSeriesUseCase;
use App\Modules\Catalog\Application\DTOs\Product\EditPage\UpdateProductSizesData;
use App\Modules\Catalog\Application\Services\AttachVariantAttributeService;
use App\Modules\Catalog\Domain\Entities\ProductEntity;
use App\Modules\Catalog\Domain\Entities\ProductSizeEntity;
use App\Modules\Catalog\Domain\Interfaces\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\ProductSizeRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\SeriesRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\SizeGridRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObjects\AttributeName;
use App\Modules\Catalog\Domain\ValueObjects\Code;
use App\Modules\Parser\Application\DTOs\ScraperProduct\ScraperProductResultData;
use App\Modules\Parser\Domain\Entities\ParserProductEntity;
use App\Modules\Parser\Domain\Entities\ParserProductSizeEntity;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserProductRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ScraperProductRepositoryInterface;
use App\Modules\Parser\Infrastructure\Jobs\LoadPublishedProductJob;
use App\Modules\Parser\Infrastructure\Mappers\SizeMapper;
use App\Modules\Parser\Infrastructure\Services\Common\CalculateParserPriceService;
use App\Modules\Parser\Infrastructure\Services\Common\PhotosProductService;
use App\Modules\Shared\Domain\ValueObjects\QueueName;
use App\Modules\Shared\Domain\ValueObjects\Slug;

/**
 * Универсальный создатель товара и parser-товара на основе данных,
 * полученных от внешнего скрапера (scraper_products).
 */
readonly class CreateProductFromScraperService
{
    public function __construct(
        private ScraperProductRepositoryInterface $scraperProductRepository,
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
        private PhotosProductService              $photosProductService,
        private FindOrCreateSeriesUseCase         $findOrCreateSeriesUseCase,
        private SeriesRepositoryInterface         $seriesRepository,
    )
    {
    }

    public function execute(ScraperProductResultData $dto): void
    {

        // По id записи scraper_products получаем brand_id и parser_category_id
        $scraperProduct = $this->scraperProductRepository->getById($dto->id);
        $parserCategoryId = $scraperProduct->parserCategoryId;
        $brandId = $scraperProduct->brandId;

        $data = $dto->product;

        $code = (string) ($data['sku'] ?? '');
        $makerId = (string) ($data['id'] ?? '');
        $name = (string) ($data['name'] ?? '');
        $url = (string) ($data['url'] ?? '');

        if ($code === '') {
            \Log::warning('Пропуск товара: пустой артикул (sku)', [
                'scraper_product_id' => $dto->id,
            ]);

            return;
        }

        // По артикулу проверяем наличие товара в базе
        if ($this->productRepository->findByCode($code) !== null) {
            return;
        }

        // Находим категорию парсера
        $category = $this->parserCategoryRepository->getById($parserCategoryId);

        if ($category->categoryId === null) {
            \Log::warning('Пропуск товара: категория парсера не привязана к категории каталога', [
                'parser_category_id' => $parserCategoryId,
                'product_name' => $name,
            ]);

            return;
        }

        $nameRu = $this->translateService->translate($name, $dto->lang);

        // Создаём товар
        $product = new ProductEntity(
            name: $nameRu,
            code: new Code($code),
            slug: new Slug($nameRu),
            mainCategoryId: $category->categoryId,
            brandId: $brandId,
        );

        $product->description = $this->translateService->translate((string) ($data['description'] ?? ''), $dto->lang);
        $product->model = (string) ($data['model'] ?? '');

        $product = $this->productRepository->save($product);

        // Атрибуты: цвет, для кого, материал (surface пока не используется).
        // Для всех атрибутов, кроме «Для кого», сначала переводим значения.
        if (!empty($data['color'])) {
            $colors = array_map(
                fn (mixed $value): string => $this->translateService->translate((string) $value, $dto->lang),
                (array) $data['color'],
            );
            $this->attachVariantAttributeService->execute($product->id, $colors, AttributeName::color());
        }

        if (!empty($data['whom'])) {
            $this->attachVariantAttributeService->execute($product->id, (array) $data['whom'], AttributeName::whom());
        }

        if (!empty($data['material'])) {
            $materials = array_map(
                fn (mixed $value): string => $this->translateService->translate((string) $value, $dto->lang),
                (array) $data['material'],
            );
            $this->attachVariantAttributeService->execute($product->id, $materials, AttributeName::material());
        }

        // Серия: если заполнена — находим/создаём и добавляем товар
        $seriesName = (string) ($data['series'] ?? '');
        if ($seriesName !== '') {
            $series = $this->findOrCreateSeriesUseCase->execute(
                $seriesName,
                $this->translateService->translate($seriesName, $dto->lang),
            );
            $this->seriesRepository->attachProducts($series->id, [$product->id]);
        }

        // Размерный ряд
        $sizesForParser = [];

        if ($category->sizeGridId !== null) {
            $this->setProductSizesUseCase->execute(new UpdateProductSizesData($product->id, $category->sizeGridId));

            // Отключаем все размеры товара — по умолчанию все варианты сетки неактивны
            $grid = $this->sizeGridRepository->getById($category->sizeGridId);
            $desiredActive = [];
            foreach ($grid->getCombinations() as $values) {
                $desiredActive[ProductSizeEntity::keyFromValues($values)] = false;
            }

            $dimensionCode = $grid->dimensions[0]->code ?? 'size';
            $productSizes = $this->productSizeRepository->getByProductId($product->id);

            foreach ($data['sizes'] ?? [] as $size) {
                if (!is_array($size)) {
                    continue;
                }

                $sizeOriginal = (string) ($size['size'] ?? '');
                if ($sizeOriginal === '') {
                    continue;
                }

                $sizeProduct = $this->sizeMapper->map($sizeOriginal);
                $available = (bool) ($size['available'] ?? true);

                // Находим размер товара и включаем его
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

                if ($available) {
                    $desiredActive[$sizeEntity->key()] = true;
                }

                $sizesForParser[] = ParserProductSizeEntity::create(
                    productSizeId: $sizeEntity->id,
                    sizeOriginal: $sizeOriginal,
                    size: $sizeProduct,
                    sku: (string) ($size['sku'] ?? ''),
                    price: (float) ($size['price'] ?? 0),
                    available: $available,
                );
            }

            // Применяем размерный ряд: включены только доступные спарсенные размеры
            $this->productSizeRepository->rebuildForProduct($product->id, $grid, $desiredActive);
        }

        // Устанавливаем цену розницы и закупку
        $this->calculate->execute((float) ($data['priceSell'] ?? 0), $product->id);

        // Загружаем фото для товара
        $images = array_values(array_filter(array_map(
            static fn (mixed $item): ?string => is_string($item) && $item !== '' ? $item : null,
            (array) ($data['images'] ?? []),
        )));
        $this->photosProductService->execute($images, $product->id, $product->name);

        // После загрузки фото публикуем товар
        LoadPublishedProductJob::dispatch($product->id)->onQueue(QueueName::PHOTO);

        // Создаём parser-товар
        $parserProduct = new ParserProductEntity(
            name: $name,
            code: $code,
        );
        $parserProduct->brandId = $brandId;
        $parserProduct->url = $url;
        $parserProduct->categoryId = $parserCategoryId;
        $parserProduct->priceSell = (float) ($data['priceSell'] ?? 0);
        $parserProduct->priceBase = (float) ($data['priceBase'] ?? 0);
        $parserProduct->data = json_encode($data);
        $parserProduct->productId = $product->id;
        $parserProduct->makerId = $makerId;

        if ($category->sizeGridId !== null) {
            foreach ($sizesForParser as $sizeForParser) {
                $parserProduct->addSize($sizeForParser);
            }
        }

        $this->parserProductRepository->save($parserProduct);
    }
}
