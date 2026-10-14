<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions\Brand;

use App\Modules\Catalog\Application\DTOs\Brand\BrandCreateData;
use App\Modules\Catalog\Domain\Entities\BrandEntity;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Shared\Domain\Entities\UserPermission;
use App\Modules\Shared\Domain\Exceptions\AccessDeniedException;
use App\Modules\Shared\Domain\ValueObjects\Slug;

readonly class CreateBrandParserUseCase
{
    public function __construct(
        private BrandRepositoryInterface $brandRepository,
    )
    {
    }

    public function execute(string $name, string $class): ?BrandEntity
    {

        $slug = new Slug($name);

        if ($this->brandRepository->existsSlug((string) $slug)) return null;

        $brand = new BrandEntity(name: trim($name), slug: $slug);
        $brand->parserClass = $class;

        return $this->brandRepository->save($brand);
    }
}
