<?php

namespace App\Modules\Shop\Presentation\Console\Commands;

use App\Modules\Catalog\Infrastructure\Models\Category;
use App\Modules\Catalog\Infrastructure\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class YandexIndexNowCommand extends Command
{
    protected $signature = 'shop:yandex';
    protected $description = 'Индексируем все страницы';

    public function handle()
    {
        $urls = [];
        $key = env('OPEN_SSL_KEY');
        /** @var Product[] $products */
        $products = Product::query()->where('published', true)->get();
        foreach ($products as $product) {
            $urls[] = route('shop.product.view', $product->slug);
        }

        /** @var Category[] $categories */
        $categories = Category::query()->where('published', true)->get();
        foreach ($categories as $category) {
            $urls[] = route('shop.category.view', $category->slug);
        }



        $response = Http::post('https://yandex.com/indexnow', [
            'host' => 'sport-shopper.ru', // без https:// и без слэша на конце
            'key' => $key,
            'urlList' => $urls,
        ]);
// Проверка ответа
        if ($response->successful()) {
            // 200 или 202 — сигнал принят
            // 202 означает, что новый ключ ещё проверяется
            $this->info('ok');
            $this->info(json_encode($response->json()));
            return;
        }

        $this->error($response->status());
        $this->info(json_encode($response->body()));
    }
}
