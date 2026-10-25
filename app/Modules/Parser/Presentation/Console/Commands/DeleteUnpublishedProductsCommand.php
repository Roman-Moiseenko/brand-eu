<?php

declare(strict_types=1);

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Modules\Catalog\Infrastructure\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class DeleteUnpublishedProductsCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'parser:delete-unpublished-products';

    protected $description = 'Окончательное удаление товаров Product с published = false (forceDelete)';

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $count = Product::where('published', false)->forceDelete();

        $this->info(sprintf('Окончательно удалено товаров: %d.', $count));

        return self::SUCCESS;
    }
}
