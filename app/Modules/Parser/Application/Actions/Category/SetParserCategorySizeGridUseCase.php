<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Actions\Category;

use App\Modules\Catalog\Domain\Interfaces\SizeGridRepositoryInterface;
use App\Modules\Parser\Domain\Entities\ParserCategoryEntity;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Shared\Domain\Entities\UserPermission;
use App\Modules\Shared\Domain\Exceptions\AccessDeniedException;

readonly class SetParserCategorySizeGridUseCase
{
    public function __construct(
        private ParserCategoryRepositoryInterface $categoryRepository,
        private SizeGridRepositoryInterface $sizeGridRepository,
    ) {}

    /**
     * Устанавливает связанную размерную сетку каталога (Catalog\SizeGrid::id)
     * для категории парсера. Если передан null — связь сбрасывается.
     */
    public function execute(int $parserCategoryId, ?int $sizeGridId, UserPermission $userPermission): ParserCategoryEntity
    {
        if (!$userPermission->can('parser.category.edit')) {
            throw new AccessDeniedException();
        }

        // Убеждаемся, что размерная сетка существует (выбросит исключение, если нет)
        if ($sizeGridId !== null) {
            $this->sizeGridRepository->getById($sizeGridId);
        }

        $category = $this->categoryRepository->getById($parserCategoryId);
        $category->sizeGridId = $sizeGridId;

        $saved = $this->categoryRepository->save($category);

        // Распространяем на все дочерние категории
        $descendantIds = $this->categoryRepository->getDescendantIds($parserCategoryId);
        if (!empty($descendantIds)) {
            $this->categoryRepository->bulkSetSizeGridId($descendantIds, $sizeGridId);
        }

        return $saved;
    }
}
