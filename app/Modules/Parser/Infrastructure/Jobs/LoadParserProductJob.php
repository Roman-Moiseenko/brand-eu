<?php

namespace App\Modules\Parser\Infrastructure\Jobs;


use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LoadParserProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $urlProduct,
        private readonly int $brandId,
    )
    {
    }

    public function handle(
        BrandRepositoryInterface $brandRepository,

    ): void
    {
        $brand = $brandRepository->getById($this->brandId);
        $parser = $brand->parser();

        $parser->GetParserProduct($this->urlProduct);

    }
}
