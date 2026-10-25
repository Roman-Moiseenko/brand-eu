<?php

namespace App\Modules\Parser\Infrastructure\Jobs;


use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserProductRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AvailabilityParserProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $productId,
    )
    {
    }

    public function handle(
        BrandRepositoryInterface $brandRepository,
        ParserProductRepositoryInterface $productRepository,

    ): void
    {
        $product = $productRepository->getById($this->productId);
        $brand = $brandRepository->getById($product->brandId);
        $parser = $brand->parser();

        $parser->GetAvailability($product->url, $product->id);

    }
}
