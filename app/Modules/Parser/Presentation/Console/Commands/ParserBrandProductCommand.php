<?php

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Infrastructure\Jobs\LoadParserProductJob;
use App\Modules\Parser\Infrastructure\Jobs\LoadParserProductsJob;
use Illuminate\Console\Command;

class ParserBrandProductCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:products';
    protected $description = 'Парсим все бренды на новые товары';

    public function handle(
        BrandRepositoryInterface $brandRepository,
        ParserCategoryRepositoryInterface $categoryRepository,
    ): void
    {
        //$brands = $brandRepository->getAll();
        $brand = $brandRepository->getByName('Babolat');
        $brands[] = $brand;
        foreach ($brands as $brand) {
            $this->info('Запускаем бренд ' . $brand->name);
            if (is_null($brand->parserClass)) continue;
            $categories = $categoryRepository->getActiveLeaves($brand->id);  //быстрая операция, запрос в БД

            foreach ($categories as $category) {
                $this->info('Категория ' . $category->nameRu);
                if (!empty($category->url)) LoadParserProductsJob::dispatch($category->url, $brand->id, $category->id);
            }

        }
    }
}
