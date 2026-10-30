<?php

declare(strict_types=1);

namespace App\Modules\Setting\Application\Actions;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ClearImageCacheUseCase
{
    /**
     * Очищает кэш изображений и запускает пересоздание миниатюр
     * через команду photo:cache-regenerate.
     *
     * @return string сообщение для пользователя
     */
    public function execute(): string
    {
        // Совместимость со старым кэшем в public/caches.
        $legacyPath = public_path('caches');
        if (File::isDirectory($legacyPath)) {
            File::cleanDirectory($legacyPath);
        }

        $exitCode = Artisan::call('photo:cache-regenerate');

        return $exitCode === 0
            ? 'Кэш изображений очищен, задачи на пересоздание миниатюр поставлены в очередь "photo".'
            : 'Ошибка при пересоздании кэша изображений';
    }
}
