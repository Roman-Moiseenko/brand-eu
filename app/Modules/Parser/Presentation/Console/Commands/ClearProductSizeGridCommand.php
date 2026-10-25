<?php

declare(strict_types=1);

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Modules\Accounting\Infrastructure\Models\Stock;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\ProductSize;
use App\Modules\Parser\Infrastructure\Models\ParserProduct;
use App\Modules\Parser\Infrastructure\Models\ParserProductSizes;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class ClearProductSizeGridCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'product:clear-size-grid
                            {categories=45,36 : main_category_id товаров через запятую}';

    protected $description = 'Удаление размерных рядов (размерных сеток) у товаров выбранных категорий и связанных parser-товаров';

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $categoryIds = array_values(array_filter(array_map(
            'intval',
            explode(',', (string) $this->argument('categories'))
        )));

        $productIds = Product::whereIn('main_category_id', $categoryIds)->pluck('id');

        // 1. Каталог: размерный ряд товаров (products_sizes) + отвязка size_grid_id + обнуление stock
        $sizeIds = ProductSize::whereIn('product_id', $productIds)->pluck('id');

        if ($sizeIds->isNotEmpty()) {
            Stock::whereIn('products_sizes_id', $sizeIds)->update(['products_sizes_id' => null]);
            ProductSize::whereIn('id', $sizeIds)->delete();
        }

        Product::whereIn('id', $productIds)->update(['size_grid_id' => null]);

        // 2. Parser: размеры parser-товаров, привязанных к найденным товарам
        $parserProductIds = ParserProduct::whereIn('product_id', $productIds)->pluck('id');
        $parserSizesDeleted = ParserProductSizes::whereIn('product_id', $parserProductIds)->delete();

        $this->info(sprintf(
            'Товаров обработано: %d (категории %s). Удалено размеров: catalog %d, parser %d.',
            $productIds->count(),
            implode(', ', $categoryIds),
            $sizeIds->count(),
            $parserSizesDeleted,
        ));

        return self::SUCCESS;
    }
}
