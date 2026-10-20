<?php

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use Illuminate\Console\Command;

class ParserTestBrandCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:test';
    protected $description = 'Парсим категории';

    const string URL = 'https://babolat-tenis.pl/5835-23345-babolat-jet-mach-4-all-court-men-dark-blue-white.html#/42-rozmiar-8_42/119-kolor-granatowy';
    const int Category = 21;

    public function handle(
        BrandRepositoryInterface          $brandRepository,
        ParserCategoryRepositoryInterface $categoryRepository,
    ): void
    {
        $this->info('Парсим товар');
        //$brands = $brandRepository->getAll();
        //$this->info('Найдено брендов ' . count($brands));
        $brand = $brandRepository->getByName('Babolat');
        $parser = $brand->parser();


        $product = $parser->GetParserProduct(
            self::URL,
            self::Category);

        dd($product);
        return;

        $categories = $categoryRepository->getActiveLeaves($brand->id);

        foreach ($categories as $category) {
            $this->info($category->url);
            $products = $parser->GetListProductsByCategory($category->url);
            dd($products);
        }
    }
}
