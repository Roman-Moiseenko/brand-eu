<?php

namespace App\Modules\Parser\Infrastructure\Jobs;


use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LoadParserProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $urlCategory,
        private readonly int $brandId,
        private readonly int $categoryId,
    )
    {
    }

    public function handle(
        BrandRepositoryInterface $brandRepository,

    ): void
    {
        $brand = $brandRepository->getById($this->brandId);
        $parser = $brand->parser();

        $products = $parser->GetListProductsByCategory($this->urlCategory);
        foreach ($products as $product) {
            LoadParserProductJob::dispatch($product, $brand->id, $this->categoryId);

        }

    }
}
