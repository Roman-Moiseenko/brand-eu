<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Job;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Одноразовая задача переноса изображений между S3-совместимыми облаками.
 *
 * Получает список относительных путей (например, "/uploads/..." и "/cache/..."),
 * скачивает каждый файл из source-облака во временный файл и загружает его
 * в target-облако. Если файл в source отсутствует — пропускает его.
 */
class CloudPhotoCopyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 3;

    /**
     * @param array{key: string, secret: string, bucket: string, endpoint: string, region: string} $source
     * @param array{key: string, secret: string, bucket: string, endpoint: string, region: string} $target
     * @param list<string> $paths Относительные пути файлов для переноса
     */
    public function __construct(
        private readonly array $source,
        private readonly array $target,
        private readonly array $paths,
    )
    {
    }

    public function handle(): void
    {
        $sourceClient = $this->makeClient($this->source);
        $targetClient = $this->makeClient($this->target);

        foreach ($this->paths as $path) {
            $key = ltrim((string)$path, '/');

            if ($key === '') {
                continue;
            }

            $tmp = null;

            try {
                if (!$this->sourceExists($sourceClient, $this->source['bucket'], $key)) {
                    $this->logSkip($path, 'файл отсутствует в источнике (timeweb)');
                    continue;
                }

                $tmp = tempnam(sys_get_temp_dir(), 'cloud_copy_');

                $sourceClient->getObject([
                    'Bucket' => $this->source['bucket'],
                    'Key' => $key,
                    'SaveAs' => $tmp,
                ]);

                $targetClient->putObject([
                    'Bucket' => $this->target['bucket'],
                    'Key' => $key,
                    'SourceFile' => $tmp,
                ]);
            } catch (\Throwable $e) {
                $this->logSkip($path, $this->errorMessage($e));
            } finally {
                if (is_string($tmp) && is_file($tmp)) {
                    @unlink($tmp);
                }
            }
        }
    }

    /**
     * Проверяет наличие файла в источнике.
     * 404 — файла нет (возвращаем false), остальные ошибки — пробрасываем выше.
     */
    private function sourceExists(S3Client $client, string $bucket, string $key): bool
    {
        try {
            $client->headObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);

            return true;
        } catch (S3Exception $e) {
            if ($e->getStatusCode() === 404) {
                return false;
            }

            throw $e;
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
                'timeout' => 300,
            ],
        ]);
    }

    private function errorMessage(\Throwable $e): string
    {
        if ($e instanceof S3Exception && $e->getAwsErrorMessage() !== null) {
            return $e->getAwsErrorMessage();
        }

        return $e->getMessage();
    }

    private function logSkip(string $path, string $reason): void
    {
        \Log::warning('CloudPhotoCopyJob: пропуск файла', [
            'path' => $path,
            'reason' => $reason,
        ]);
    }
}
