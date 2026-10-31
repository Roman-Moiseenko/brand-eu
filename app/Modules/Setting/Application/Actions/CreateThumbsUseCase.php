<?php

declare(strict_types=1);

namespace App\Modules\Setting\Application\Actions;

use App\Modules\Shared\Domain\ValueObjects\QueueName;
use App\Modules\Shared\Infrastructure\Job\CreatePhotoThumbsJob;
use App\Modules\Shared\Infrastructure\Models\Photo;

/**
 * Ставит в очередь задачи создания thumbs для всех фото указанных model_type.
 */
readonly class CreateThumbsUseCase
{
    private const DEFAULT_MODEL_TYPES = [
        'catalog.product',
        'catalog.category',
        'catalog.room',
        'catalog.brand',
    ];

    /**
     * @return string сообщение для пользователя
     */
    public function execute(): string
    {
        $ids = Photo::query()
            ->whereIn('model_type', self::DEFAULT_MODEL_TYPES)
            ->pluck('id');

        foreach ($ids as $id) {
            CreatePhotoThumbsJob::dispatch((int)$id)->onQueue(QueueName::PHOTO);
        }

        return sprintf('Поставлено задач создания миниатюр в очередь "%s": %d.', QueueName::PHOTO, $ids->count());
    }
}
