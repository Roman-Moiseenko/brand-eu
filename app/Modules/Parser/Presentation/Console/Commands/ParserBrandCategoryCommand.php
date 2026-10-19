<?php

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use Illuminate\Console\Command;

class ParserBrandCategoryCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:category';
    protected $description = 'Парсим все бренды на новые категории';

    public function handle(BrandRepositoryInterface $brandRepository): void
    {
        $this->info('Парсим категории');
        $brands = $brandRepository->getAll();
        $this->info('Найдено брендов ' . count($brands));
        // $brands[] = $brandRepository->getByName('Babolat');
        foreach ($brands as $brand) {
            if (is_null($brand->parserClass)) continue;

            $parser = $brand->parser();
            $categories = $parser->getListCategories(); //Долгая операция
            $this->info('brand ' . $brand->name);

            foreach ($categories as $category) {
                $parser->addCategory($category);
                $this->info('category Добавлена');
            }

        }
    }
}
