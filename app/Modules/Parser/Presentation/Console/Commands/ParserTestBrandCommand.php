<?php

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use Illuminate\Console\Command;

class ParserTestBrandCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:category';
    protected $description = 'Парсим категории';

    public function handle(BrandRepositoryInterface $brandRepository): void
    {
        //MAINDO Парсим категории.! Запуск ручной, сделать в админке
        $this->info('Парсим категории');
        $brands = $brandRepository->getAll();
        $this->info('Найдено брендов ' . count($brands));
       // $brands[] = $brandRepository->getByName('Babolat');
        foreach ($brands as $brand) {
            if (is_null($brand->parserClass)) continue;

            //FixMe Запуск через Job
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
