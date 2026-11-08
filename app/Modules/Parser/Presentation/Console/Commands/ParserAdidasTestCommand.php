<?php

declare(strict_types=1);

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Infrastructure\Models\ScraperCategory;
use App\Modules\Parser\Infrastructure\Services\AdidasParserService;
use Illuminate\Console\Command;

class ParserAdidasTestCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:adidas-test';
    protected $description = 'Тестовый запуск ScraperCategories для Adidas (без Job)';

    public function handle(BrandRepositoryInterface $brandRepository): void
    {
        $brand = $brandRepository->getByName('Adidas');

        if ($brand === null) {
            $this->error('Бренд Adidas не найден. Сначала запустите parser:adidas-category');
            return;
        }

        if (is_null($brand->parserClass)) {
            $brand->parserClass = AdidasParserService::class;
            $brand = $brandRepository->save($brand);
        }

        $this->info('Бренд: ' . $brand->name . ' (#' . $brand->id . ')');
        $brand->parser()->ScraperCategories();
        $brand->parser()->ScraperAvailable();

        $count = ScraperCategory::where('brand_id', (int) $brand->id)->count();
        $this->info('Записей в scraper_categories для бренда: ' . $count);
    }
}
