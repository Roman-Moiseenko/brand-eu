<?php

declare(strict_types=1);

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use Illuminate\Console\Command;

class ParserScraperAvailableCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:scraper-available';
    protected $description = 'Доступность товаров скрапера для всех брендов с классом парсера';

    public function handle(BrandRepositoryInterface $brandRepository): void
    {
        $this->info('Доступность товаров скрапера...');

        foreach ($brandRepository->getAll() as $brand) {
            if ($brand->parserClass === null) {
                continue;
            }

            $this->info('Бренд: ' . $brand->name);
            $brand->parser()->ScraperAvailable();
        }

        $this->info('Готово.');
    }
}
