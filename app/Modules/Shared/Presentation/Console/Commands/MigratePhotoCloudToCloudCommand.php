<?php

declare(strict_types=1);

namespace App\Modules\Shared\Presentation\Console\Commands;

use App\Modules\Setting\Entity\Settings;
use App\Modules\Shared\Domain\ValueObjects\QueueName;
use App\Modules\Shared\Infrastructure\Job\CloudPhotoCopyJob;
use App\Modules\Shared\Infrastructure\Models\Photo;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use function class_basename;

/**
 * Одноразовая команда переноса всех изображений из облака Timeweb в облако Beget.
 *
 * Все креды и пути хранятся прямо здесь, в константах ниже.
 * Достаточно заполнить ACCESS_KEY, SECRET_KEY и PATH (имя бакета) для обоих облаков.
 *
 * Перенос выполняется через очередь "photo" задачами CloudPhotoCopyJob.
 */
class MigratePhotoCloudToCloudCommand extends Command
{
    protected $signature = 'photo:cloud-to-cloud
                            {--chunk=100 : Количество путей в одной задаче очереди}
                            {--test : Тест: поставить в очередь только одну задачу}
                            {--check : Проверить подключение к Timeweb и Beget и выйти}';

    protected $description = 'Перенос изображений (оригиналы + нарезки thumbs) из Timeweb S3 в Beget S3';

    // =====================================================================
    // ИСТОЧНИК — TIMEWEB (откуда копируем)
    // =====================================================================
    private const string TIMEWEB_ACCESS_KEY = '6JLSEIR8LLALOYR1H4UE';
    private const string TIMEWEB_SECRET_KEY = 'vJsd7olJk11c3vsKJCoNs5BBwCzyrad83CylrZ9s';
    /** PATH — имя бакета (bucket) в Timeweb Cloud S3 */
    private const string TIMEWEB_PATH = '9528803c-1b57-4eb3-846a-35facf8eda62';
    private const string TIMEWEB_ENDPOINT = 'https://s3.timeweb.com';
    private const string TIMEWEB_REGION = 'ru-1';

    // =====================================================================
    // НАЗНАЧЕНИЕ — BEGET (куда копируем)
    // =====================================================================
    private const string BEGET_ACCESS_KEY = 'P1E16LSB84H3AKS00Y6C';
    private const string BEGET_SECRET_KEY = 'S2UvqHZZPjeFRQqxNMuI6QHqQ9Y4rRx3qZI3szPH';
    /** PATH — имя бакета (bucket) в Beget S3 */
    private const string BEGET_PATH = '374163844b37-sport';
    private const string BEGET_ENDPOINT = 'https://s3.ru1.storage.beget.cloud';
    private const string BEGET_REGION = 'ru-1';

    public function __construct(
        private readonly Settings $settings,
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $source = $this->sourceConfig();
        $target = $this->targetConfig();

        if ($source['key'] === '' || $source['secret'] === '' || $source['bucket'] === ''
            || $target['key'] === '' || $target['secret'] === '' || $target['bucket'] === '') {
            $this->error('Заполните константы ACCESS_KEY, SECRET_KEY и PATH для Timeweb и Beget в классе команды MigratePhotoCloudToCloudCommand.');
            return self::FAILURE;
        }

        if ($this->option('check')) {
            $this->check('Timeweb (источник)', $source);
            $this->check('Beget (назначение)', $target);
            return self::SUCCESS;
        }

        $paths = $this->collectPaths();

        if ($paths === []) {
            $this->info('Путей для переноса не найдено.');
            return self::SUCCESS;
        }

        $chunkSize = max(1, (int)$this->option('chunk'));
        $chunks = array_chunk($paths, $chunkSize);
        $progress = $this->output->createProgressBar(count($chunks));
        $progress->start();

        foreach ($chunks as $chunk) {
            CloudPhotoCopyJob::dispatch($source, $target, $chunk)->onQueue(QueueName::PHOTO);

            $progress->advance();

            if ($this->option('test')) {
                $this->newLine();
                $this->info('Тестовый запуск: в очередь поставлена одна задача.');
                break;
            }
        }

        $progress->finish();
        $this->newLine();

        $this->info(sprintf(
            'В очередь "%s" поставлено задач: %d (путей: %d).',
            QueueName::PHOTO,
            count($chunks),
            count($paths),
        ));

        return self::SUCCESS;
    }

    /**
     * Список всех относительных путей: оригиналы из /uploads и нарезки thumbs из /cache.
     *
     * @return list<string>
     */
    private function collectPaths(): array
    {
        $paths = [];

        Photo::query()
            ->select(['id', 'model_type', 'imageable_type', 'imageable_id', 'file'])
            ->chunk(500, function ($photos) use (&$paths) {
                foreach ($photos as $photo) {
                    if (empty($photo->file)) {
                        continue;
                    }

                    $base = $this->patternGeneratePath(
                        (string)$photo->model_type,
                        (string)$photo->imageable_type,
                        (int)$photo->imageable_id,
                    );

                    // Оригинал: /uploads/{module}/{model}/{imageable_id}/{file}
                    $paths[] = '/uploads' . $base . $photo->file;

                    // Нарезки: /cache/{module}/{model}/{imageable_id}/{thumb}_{photoId}.{ext}
                    $ext = pathinfo($photo->file, PATHINFO_EXTENSION);
                    foreach ($this->settings->getImage()->thumbs as $thumb) {
                        $paths[] = '/cache' . $base . $thumb['name'] . '_' . $photo->id . '.' . $ext;
                    }
                }
            });

        return $paths;
    }

    /**
     * Генерация базового пути сущности: /{module}/{model}/{imageable_id}/
     * Логика повторяет Photo::patternGeneratePath().
     */
    private function patternGeneratePath(string $modelType, string $imageableType, int $imageableId): string
    {
        if ($modelType !== '') {
            [$module, $model] = explode('.', $modelType, 2);
            return '/' . $module . '/' . $model . '/' . $imageableId . '/';
        }

        return '/' . Str::slug(class_basename($imageableType)) . '/' . $imageableId . '/';
    }

    /**
     * Диагностика подключения к облаку.
     */
    private function check(string $label, array $config): void
    {
        $this->line('');
        $this->line($label);
        $this->line('  endpoint: ' . $config['endpoint']);
        $this->line('  region:   ' . $config['region']);
        $this->line('  bucket:   ' . $config['bucket']);

        $client = $this->makeClient($config);

        try {
            $client->headBucket(['Bucket' => $config['bucket']]);
            $this->info('  OK: бакет доступен (headBucket).');
        } catch (S3Exception $e) {
            $this->error(sprintf(
                '  Ошибка headBucket: HTTP %s [%s] %s',
                $e->getStatusCode(),
                $e->getAwsErrorCode() ?? 'Unknown',
                $e->getAwsErrorMessage() ?? $e->getMessage(),
            ));
            $this->listBuckets($client);
            return;
        }

        try {
            $result = $client->listObjectsV2(['Bucket' => $config['bucket'], 'MaxKeys' => 1]);
            $this->info('  OK: listObjectsV2 работает, объектов в первой странице: ' . count($result['Contents'] ?? []) . '.');
        } catch (S3Exception $e) {
            $this->warn(sprintf(
                '  listObjectsV2: HTTP %s [%s] %s',
                $e->getStatusCode(),
                $e->getAwsErrorCode() ?? 'Unknown',
                $e->getAwsErrorMessage() ?? $e->getMessage(),
            ));
        }
    }

    /**
     * Выводит бакеты, доступные для переданных ключей.
     */
    private function listBuckets(S3Client $client): void
    {
        try {
            $result = $client->listBuckets();
            $names = array_map(
                static fn (array $b): string => (string)$b['Name'],
                $result['Buckets'] ?? [],
            );
            $this->line('  Бакеты, доступные для этих ключей: ' . ($names === [] ? 'нет' : implode(', ', $names)));
        } catch (S3Exception $e) {
            $this->warn(sprintf(
                '  listBuckets: HTTP %s [%s] %s',
                $e->getStatusCode(),
                $e->getAwsErrorCode() ?? 'Unknown',
                $e->getAwsErrorMessage() ?? $e->getMessage(),
            ));
        }
    }

    /**
     * @param array{key: string, secret: string, bucket: string, endpoint: string, region: string} $config
     */
    private function makeClient(array $config): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => $config['region'] ?? 'ru-1',
            'endpoint' => $config['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
            'http' => [
                'connect_timeout' => 10,
                'timeout' => 30,
            ],
        ]);
    }

    /**
     * @return array{key: string, secret: string, bucket: string, endpoint: string, region: string}
     */
    private function sourceConfig(): array
    {
        return [
            'key' => self::TIMEWEB_ACCESS_KEY,
            'secret' => self::TIMEWEB_SECRET_KEY,
            'bucket' => self::TIMEWEB_PATH,
            'endpoint' => self::TIMEWEB_ENDPOINT,
            'region' => self::TIMEWEB_REGION,
        ];
    }

    /**
     * @return array{key: string, secret: string, bucket: string, endpoint: string, region: string}
     */
    private function targetConfig(): array
    {
        return [
            'key' => self::BEGET_ACCESS_KEY,
            'secret' => self::BEGET_SECRET_KEY,
            'bucket' => self::BEGET_PATH,
            'endpoint' => self::BEGET_ENDPOINT,
            'region' => self::BEGET_REGION,
        ];
    }
}
