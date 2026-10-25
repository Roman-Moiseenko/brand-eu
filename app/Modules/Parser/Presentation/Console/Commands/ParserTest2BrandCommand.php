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
    const array URLS = [
        'https://babolat-tenis.pl/5835-23345-babolat-jet-mach-4-all-court-men-dark-blue-white.html#/42-rozmiar-8_42/119-kolor-granatowy',
        'https://babolat-tenis.pl/5859-23512-babolat-jet-mach-4-clay-men-dark-blue-white.html#/44-rozmiar-9_43/119-kolor-granatowy',
        'https://babolat-tenis.pl/5574-22513-babolat-jet-mach-4-all-court-men-cream-dark-blue.html#/39-rozmiar-65_40/210-kolor-bialo_granatowy',
        'https://babolat-tenis.pl/5581-22601-babolat-jet-mach-4-clay-men-cherry-tomato-white.html#/43-rozmiar-85_425/209-kolor-czerwono_bialy',
        'https://babolat-tenis.pl/5577-22551-babolat-jet-mach-4-clay-men-black-aero.html#/39-rozmiar-65_40/84-kolor-czarno_zolty',
        'https://babolat-tenis.pl/5301-21062-babolat-jet-mach-3-all-court-men-black-white.html#/44-rozmiar-9_43/107-kolor-czarno_bialy',
        'https://babolat-tenis.pl/5300-21044-babolat-jet-mach-3-all-court-men-white-dark-grey.html#/41-rozmiar-75_41/329-kolor-bialo_ciemnoszare',
        'https://babolat-tenis.pl/5303-21091-babolat-jet-mach-3-clay-men-white-dark-grey.html#/43-rozmiar-85_425/329-kolor-bialo_ciemnoszare',
        'https://babolat-tenis.pl/5302-21074-babolat-jet-mach-3-all-court-men-dark-blue-silver.html#/41-rozmiar-75_41/330-kolor-granatowo_srebrny',
        'https://babolat-tenis.pl/5304-21102-babolat-jet-mach-3-clay-men-dark-blue-silver.html#/39-rozmiar-65_40/330-kolor-granatowo_srebrny',
        'https://babolat-tenis.pl/5796-23207-babolat-jet-tere-2-all-court-wim-men-white-b-navy.html#/41-rozmiar-75_41/210-kolor-bialo_granatowy',
    ];

    public function handle(
        BrandRepositoryInterface          $brandRepository,
        ParserCategoryRepositoryInterface $categoryRepository,
    ): void
    {

        $this->info('!!!!');
        //$brands = $brandRepository->getAll();
        //$this->info('Найдено брендов ' . count($brands));
        $brand = $brandRepository->getByName('Babolat'); //New Balance
        $parser = $brand->parser();

        $url = 'https://babolat-tenis.pl/5302-21074-babolat-jet-mach-3-all-court-men-dark-blue-silver.html#/41-rozmiar-75_41/330-kolor-granatowo_srebrny';
        $parser->GetAvailability($url, 110);

        $this->info('!!!!');
    }
}
