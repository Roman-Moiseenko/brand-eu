<?php

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Infrastructure\Jobs\LoadParserProductsJob;
use App\Modules\Parser\Infrastructure\Models\ParserProduct;
use Illuminate\Console\Command;

class ParserAvailabilityCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:availability';
    protected $description = 'Все товары availability';
    public function handle(
    ): void
    {
        $this->info('Все товары availability');

        ParserProduct::query()->update(['availability' => true]);
        $this->info('!');
    }
}
