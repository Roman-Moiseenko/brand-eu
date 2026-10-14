<?php

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use Illuminate\Console\Command;

class ParserBrandCategoryCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:categories-update';
    protected $description = 'Парсим все бренды на новые категории';

    public function handle(BrandRepositoryInterface $brandRepository): void
    {
        $brands = $brandRepository->getAll();

        foreach ($brands as $brand) {
            if (is_null($brand->parserClass)) continue;

            //FixMe Запуск через Job
            $parser = $brand->parser();
            $categories = $parser->getListCategories(); //Долгая операция
            foreach ($categories as $category) {
                $parser->addCategory($category);
            }
            //<--

        }
    }
}
