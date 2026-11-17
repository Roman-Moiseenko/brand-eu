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
                $tmp = tempnam(sys_get_temp_dir(), 'cloud_copy_');

                // Скачивание из источника; если файла нет — SDK бросит NoSuchKey (404).
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
            } catch (S3Exception $e) {
                if ($this->isNotFound($e)) {
                    $this->logSkip($path, 'файл отсутствует в источнике (timeweb)');
                } else {
                    $this->logSkip($path, $this->errorMessage($e));
                }
            } catch (\Throwable $e) {
                $this->logSkip($path, $e->getMessage());
            } finally {
                if (is_string($tmp) && is_file($tmp)) {
                    @unlink($tmp);
                }
            }
        }
    }

    /**
     * Ошибки, которые означают отсутствие файла/бакета в источнике.
     */
    private function isNotFound(S3Exception $e): bool
    {
        if ($e->getStatusCode() === 404) {
            return true;
        }

        return in_array($e->getAwsErrorCode(), ['NoSuchKey', 'NotFound', 'NoSuchBucket'], true);
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
        if ($e instanceof S3Exception) {
            $status = $e->getStatusCode();
            $code = $e->getAwsErrorCode() ?? 'UnknownCode';
            $message = $e->getAwsErrorMessage() ?: ($e->getMessage() ?: 'нет сообщения');

            if ($status === 403) {
                return sprintf('HTTP %s [%s] %s (доступ запрещён)', $status, $code, $message);
            }

            return sprintf('HTTP %s [%s] %s', $status, $code, $message);
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
