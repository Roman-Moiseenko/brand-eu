<?php

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Infrastructure\Jobs\LoadParserProductJob;
use App\Modules\Parser\Infrastructure\Jobs\LoadParserProductsJob;
use App\Modules\Parser\Infrastructure\Services\NBParserService;
use Illuminate\Console\Command;

class ParserNBSeriesCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:nb-series';
    protected $description = 'Парсим все серии из NB';

    public function handle(NBParserService $NBParserService,): void
    {
        //$brands = $brandRepository->getAll();
        $NBParserService->GetSeries();


    }
}
