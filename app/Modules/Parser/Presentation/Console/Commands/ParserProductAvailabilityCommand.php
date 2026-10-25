<?php

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserProductRepositoryInterface;
use App\Modules\Parser\Infrastructure\Jobs\AvailabilityParserProductJob;
use App\Modules\Parser\Infrastructure\Jobs\LoadParserProductsJob;
use App\Modules\Parser\Infrastructure\Models\ParserProduct;
use App\Modules\Shared\Domain\ValueObjects\QueueName;
use Illuminate\Console\Command;

class ParserProductAvailabilityCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:availability';
    protected $description = 'Парсим товары на доступность и цену';
    public function handle(
        ParserProductRepositoryInterface $repository
    ): void
    {
        $this->info('Запуск');

        $products = $repository->getAll();
        $progress = $this->output->createProgressBar(count($products));
        $progress->start();
        foreach ($products as $product) {
            if ($product->availability)
                AvailabilityParserProductJob::dispatch($product->id);
            $progress->advance();
        }
        $progress->finish();
        $this->newLine();
        $this->info(sprintf(
            'В очередь "%s" поставлено задач: %d.',
            QueueName::DEFAULT,
            count($products),

        ));
    }
}
