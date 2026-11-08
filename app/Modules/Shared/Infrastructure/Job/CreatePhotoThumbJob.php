<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Job;

use App\Modules\Setting\Entity\Settings;
use App\Modules\Shared\Infrastructure\Models\Photo;
use App\Modules\Shared\Infrastructure\Services\PhotoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Создаёт все thumbs (mini, catalog, card, original) для одного фото.
 * В конструктор приходит только id записи из таблицы photos.
 */
class CreatePhotoThumbJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Photo $photo,
        private readonly string $thumb,
    )
    {
    }

    public function handle(PhotoService $photoService): void
    {
        try {

                $photoService->createThumbs(
                    photoId: $this->photo->id,
                    modelType: $this->photo->model_type,
                    imageableId: (int)$this->photo->imageable_id,
                    fileName: $this->photo->file,
                    thumb: $this->thumb,
                );

        } catch (\Throwable $e) {

        }
    }
}
