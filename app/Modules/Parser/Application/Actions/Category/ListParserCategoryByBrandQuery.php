<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Actions\Category;

use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Domain\Entities\ParserCategoryEntity;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Shared\Domain\Entities\UserPermission;
use App\Modules\Shared\Domain\Exceptions\AccessDeniedException;

/**
 * Возвращает дерево категорий парсера по slug бренда.
 */
readonly class ListParserCategoryByBrandQuery
{
    public function __construct(
        private BrandRepositoryInterface $brandRepository,
        private ParserCategoryRepositoryInterface $categoryRepository,
    ) {}

    /**
     * @return ParserCategoryEntity[]
     */
    public function execute(?string $slug, UserPermission $userPermission): array
    {
        if (!$userPermission->can('parser.category.view')) {
            throw new AccessDeniedException();
        }

        if ($slug === null || $slug === '') {
            return [];
        }

        $brand = $this->brandRepository->getBySlug($slug);

        if ($brand === null) {
            return [];
        }

        return $this->categoryRepository->getTreeByBrandId($brand->id);
    }
}
