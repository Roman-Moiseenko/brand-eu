<?php

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Infrastructure\Jobs\LoadParserProductsJob;
use Illuminate\Console\Command;

class ParserTestBrandCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:test';
    protected $description = 'Парсим категории';
//NEW BALANCE
    const int Category_NB = 33;
    const string URL_NB = 'meskie/obuwie/klasyczne/';

///BABOLAT
    const string URL = 'https://babolat-tenis.pl/5835-23345-babolat-jet-mach-4-all-court-men-dark-blue-white.html#/42-rozmiar-8_42/119-kolor-granatowy';
    const string URL2 = 'https://babolat-tenis.pl/3436-pilki-babolat-gold-academy-worek-72-szt.html';
    const array URLS = [
        "https://babolat-tenis.pl/135-266-naciag-babolat-ifeel-68-102-m.html#/54-rozmiar-068/79-kolor-bialy",
        "https://babolat-tenis.pl/135-267-naciag-babolat-ifeel-68-102-m.html#/54-rozmiar-068/76-kolor-czerwony",
        "https://babolat-tenis.pl/135-268-naciag-babolat-ifeel-68-102-m.html#/54-rozmiar-068/75-kolor-czarny",
        "https://babolat-tenis.pl/135-270-naciag-babolat-ifeel-68-102-m.html#/54-rozmiar-068/72-kolor-niebieski",
    ];
    const int Category = 21;

    public function handle(
        BrandRepositoryInterface          $brandRepository,
        ParserCategoryRepositoryInterface $categoryRepository,
    ): void
    {
        $this->info('Парсим товар');
        //$brands = $brandRepository->getAll();
        //$this->info('Найдено брендов ' . count($brands));
        $brand = $brandRepository->getByName('New Balance');
        $parser = $brand->parser();
        $categories = $categoryRepository->getActiveLeaves($brand->id);
        //$products = $parser->GetListProductsByCategory(self::URL_NB);

        $categories = $categoryRepository->getActiveLeaves($brand->id);  //быстрая операция, запрос в БД
        foreach ($categories as $category) {
            $this->info('Категория ' . $category->nameRu);
            $products = $parser->GetListProductsByCategory($category->url);
            foreach ($products as $product) {
                $productEntity = $parser->GetParserProduct($product, $category->id);
                dd($productEntity);
            }


        }


        return;
        /*
                $categories = $categoryRepository->getActiveLeaves($brand->id);

                foreach ($categories as $category) {
                    $this->info($category->url);
                    $products = $parser->GetListProductsByCategory($category->url);
                    dd($products);
                }
                */
    }
}
