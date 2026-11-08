<?php

declare(strict_types=1);

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use Illuminate\Console\Command;

class ParserScraperCategoriesCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:scraper-categories';
    protected $description = 'Наполняет scraper_categories категориями для всех брендов с классом парсера';

    public function handle(BrandRepositoryInterface $brandRepository): void
    {
        $this->info('Наполняем scraper_categories...');

        foreach ($brandRepository->getAll() as $brand) {
            if ($brand->parserClass === null) {
                continue;
            }

            $this->info('Бренд: ' . $brand->name);
            $brand->parser()->ScraperCategories();
        }

        $this->info('Готово.');
    }
}
