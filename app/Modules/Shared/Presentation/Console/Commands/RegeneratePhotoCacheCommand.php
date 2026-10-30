<?php

declare(strict_types=1);

namespace App\Modules\Shared\Presentation\Console\Commands;

use App\Modules\Shared\Application\Interfaces\PhotoStorageInterface;
use App\Modules\Shared\Domain\ValueObjects\QueueName;
use App\Modules\Shared\Infrastructure\Job\CreatePhotoThumbsJob;
use App\Modules\Shared\Infrastructure\Models\Photo;
use App\Modules\Shared\Infrastructure\Services\PhotoService;
use Illuminate\Console\Command;

class RegeneratePhotoCacheCommand extends Command
{
    protected $signature = 'photo:cache-regenerate
                            {--model-type=* : Список model_type (можно указать несколько раз)}';

    protected $description = 'Пересоздаёт кэш миниатюр: удаляет /cache и ставит в очередь photo задачи для всех фото указанных model_type';

    private const DEFAULT_MODEL_TYPES = [
        'catalog.product',
        'catalog.category',
        'catalog.room',
        'catalog.brand',
    ];

    public function __construct(
        private readonly PhotoStorageInterface $storage,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelTypes = $this->option('model-type');
        if (!is_array($modelTypes) || $modelTypes === []) {
            $modelTypes = self::DEFAULT_MODEL_TYPES;
        }
        $modelTypes = array_values(array_unique($modelTypes));

        $this->info('Удаляю кэш миниатюр...');

        $cacheFiles = $this->storage->listFiles(PhotoService::URL_THUMB);
        $deleteProgress = $this->output->createProgressBar(count($cacheFiles));
        $deleteProgress->start();
        foreach ($cacheFiles as $file) {
            $this->storage->delete($file);
            $deleteProgress->advance();
        }
        $deleteProgress->finish();
        $this->newLine(2);

        $ids = Photo::query()
            ->whereIn('model_type', $modelTypes)
            ->pluck('id');

        $this->info(sprintf('Ставлю задачи в очередь "%s"...', QueueName::PHOTO));

        $dispatchProgress = $this->output->createProgressBar($ids->count());
        $dispatchProgress->start();
        foreach ($ids as $id) {
            CreatePhotoThumbsJob::dispatch((int)$id)->onQueue(QueueName::PHOTO);
            $dispatchProgress->advance();
        }
        $dispatchProgress->finish();
        $this->newLine(2);

        $this->info(sprintf(
            'Удалено файлов кэша: %d. Поставлено задач: %d.',
            count($cacheFiles),
            $ids->count(),
        ));

        return self::SUCCESS;
    }
}
