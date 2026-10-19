<?php

namespace App\Modules\Parser\Infrastructure\Jobs\old;

use App\Modules\Parser\Application\Services\LoadParserProductIkeaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LoadProductsIkeaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly string $ikeaId)
    {
    }

    public function handle(LoadParserProductIkeaService $service): void
    {
        $products = $service->GetListProductsByCategory($this->ikeaId);

        foreach ($products as $product) {
            LoadProductIkeaJob::dispatch($product); //$entity = $this->CreateParserProduct($product);
        }
    }
}
