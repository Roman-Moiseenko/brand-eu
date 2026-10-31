<?php

declare(strict_types=1);

namespace App\Modules\Setting\Application\Actions;

use App\Modules\Shared\Application\Interfaces\PhotoStorageInterface;
use App\Modules\Shared\Domain\ValueObjects\QueueName;
use App\Modules\Shared\Infrastructure\Job\ReCreatePhotoThumbsJob;
use App\Modules\Shared\Infrastructure\Models\Photo;
use App\Modules\Shared\Infrastructure\Services\PhotoService;

/**
 * Удаляет кеш миниатюр и пересоздаёт его через очередь.
 */
readonly class ReCreateThumbsUseCase
{
    private const DEFAULT_MODEL_TYPES = [
        'catalog.product',
        'catalog.category',
        'catalog.room',
        'catalog.brand',
    ];

    public function __construct(
        private PhotoStorageInterface $storage,
    )
    {
    }

    /**
     * @return string сообщение для пользователя
     */
    public function execute(): string
    {
        $this->storage->deleteDirectory(PhotoService::URL_THUMB);

        $ids = Photo::query()
            ->whereIn('model_type', self::DEFAULT_MODEL_TYPES)
            ->pluck('id');

        foreach ($ids as $id) {
            ReCreatePhotoThumbsJob::dispatch((int)$id)->onQueue(QueueName::PHOTO);
        }

        return sprintf('Кеш миниатюр удалён, поставлено задач в очередь "%s": %d.', QueueName::PHOTO, $ids->count());
    }
}
