<?php

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use Illuminate\Console\Command;

class ParserTestBrandCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:test';
    protected $description = 'Тестирование парсера';

    public function handle(BrandRepositoryInterface $brandRepository): void
    {
        $this->info('Тестирование парсера');
        $brands = $brandRepository->getAll();
        $this->info('Найдено брендов ' . count($brands));
        //$brands[] = $brandRepository->getByName('Babolat');
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
