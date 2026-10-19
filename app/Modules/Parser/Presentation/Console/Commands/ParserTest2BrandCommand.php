<?php

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use Illuminate\Console\Command;

class ParserTest2BrandCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:test2';
    protected $description = 'Парсим товары';

    const string URL = 'https://babolat-tenis.pl/29-badminton-naciagi';

    public function handle(
        BrandRepositoryInterface          $brandRepository,
        ParserCategoryRepositoryInterface $categoryRepository,
    ): void
    {

        $this->info('Парсим товары');
        //$brands = $brandRepository->getAll();
        //$this->info('Найдено брендов ' . count($brands));
        $brand = $brandRepository->getByName('Babolat');
        $parser = $brand->parser();

        $products = $parser->GetListProductsByCategory(self::URL);
        dd($products);

    }
}
