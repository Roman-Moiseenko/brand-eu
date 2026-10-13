<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Actions\Brand;

use App\Modules\Catalog\Application\DTOs\Brand\BrandListData;
use App\Modules\Catalog\Domain\Entities\BrandEntity;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Shared\Domain\Entities\UserPermission;
use App\Modules\Shared\Domain\Exceptions\AccessDeniedException;

/**
 * Возвращает список брендов, у которых задан класс парсера (parser_class != null).
 */
readonly class ListParserBrandQuery
{
    public function __construct(
        private BrandRepositoryInterface $brandRepository,
    ) {}

    /**
     * @return BrandListData[]
     */
    public function execute(UserPermission $userPermission): array
    {
        if (!$userPermission->can('parser.category.view')) {
            throw new AccessDeniedException();
        }

        $brands = array_values(array_filter(
            $this->brandRepository->getAll(),
            fn(BrandEntity $brand) => $brand->parserClass !== null,
        ));

        return array_map(
            fn(BrandEntity $brand) => BrandListData::fromEntity($brand),
            $brands,
        );
    }
}
