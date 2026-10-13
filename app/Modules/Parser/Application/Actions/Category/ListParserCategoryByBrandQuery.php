<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Actions\Category;

use App\Modules\Parser\Domain\Entities\ParserCategoryEntity;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Shared\Domain\Entities\UserPermission;
use App\Modules\Shared\Domain\Exceptions\AccessDeniedException;

readonly class ListParserCategoryByBrandQuery
{
    public function __construct(
        private ParserCategoryRepositoryInterface $categoryRepository,
    ) {}

    /**
     * @return ParserCategoryEntity[]
     */
    public function execute(int $brandId, UserPermission $userPermission): array
    {
        if (!$userPermission->can('parser.category.view')) {
            throw new AccessDeniedException();
        }

        return $this->categoryRepository->getTreeByBrandId($brandId);
    }
}
