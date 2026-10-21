<?php

namespace App\Modules\Parser\Infrastructure\Jobs;


use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\ProductRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LoadPublishedProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $productId,
    )
    {
    }

    public function handle(
        ProductRepositoryInterface $repository,

    ): void
    {
        $product = $repository->getById($this->productId);
        $product->publish();
        $repository->save($product);
    }
}
