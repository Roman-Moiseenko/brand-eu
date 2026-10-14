<?php

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use Illuminate\Console\Command;

class ParserBrandProductCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:products-update';
    protected $description = 'Парсим все бренды на новые товары';

    public function handle(
        BrandRepositoryInterface $brandRepository,
        ParserCategoryRepositoryInterface $categoryRepository,
    ): void
    {
        $brands = $brandRepository->getAll();

        foreach ($brands as $brand) {
            if (is_null($brand->parserClass)) continue;
            $categories = $categoryRepository->getActiveLeaves($brand->id);  //быстрая операция, запрос в БД
            $parser = $brand->parser();

            foreach ($categories as $category) {
                //FixMe Запуск через Job
                $products = $parser->GetListProductsByCategory($category->code);
                foreach ($products as $product) {
                    $parser->GetParserProduct($product);
                }
                //<--
            }

        }
    }
}
