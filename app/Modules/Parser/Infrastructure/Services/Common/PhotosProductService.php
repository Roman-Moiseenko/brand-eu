<?php

namespace App\Modules\Parser\Infrastructure\Services\Common;

use App\Modules\Shared\Application\DTOs\JobPhotoLoadData;
use App\Modules\Shared\Domain\ValueObjects\PhotoType;
use App\Modules\Shared\Domain\ValueObjects\QueueName;
use App\Modules\Shared\Infrastructure\Job\LoadPhotoByUrlJob;

readonly class PhotosProductService
{

    public function execute(array $items, int $productId, string $alt): void
    {
        foreach ($items as $imageItem) {
            $dtoPhoto = new JobPhotoLoadData(
                imageableId: $productId,
                modelType: 'catalog.product',
                type: PhotoType::GALLERY,
                url: $imageItem,
                isProxy: false,
                alt: $alt,
            );
            LoadPhotoByUrlJob::dispatch($dtoPhoto)->onQueue(QueueName::PHOTO);
        }
    }
}
