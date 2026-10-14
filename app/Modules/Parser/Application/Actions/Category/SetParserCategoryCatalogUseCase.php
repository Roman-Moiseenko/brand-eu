<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Actions\Category;

use App\Modules\Catalog\Domain\Interfaces\CategoryRepositoryInterface;
use App\Modules\Parser\Domain\Entities\ParserCategoryEntity;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Shared\Domain\Entities\UserPermission;
use App\Modules\Shared\Domain\Exceptions\AccessDeniedException;

readonly class SetParserCategoryCatalogUseCase
{
    public function __construct(
        private ParserCategoryRepositoryInterface $categoryRepository,
        private CategoryRepositoryInterface $catalogCategoryRepository,
    ) {}

    /**
     * Устанавливает связанную категорию каталога (Catalog\Category::id)
     * для категории парсера.
     */
    public function execute(int $parserCategoryId, int $categoryId, UserPermission $userPermission): ParserCategoryEntity
    {
        if (!$userPermission->can('parser.category.edit')) {
            throw new AccessDeniedException();
        }

        // Убеждаемся, что категория каталога существует (выбросит исключение, если нет)
        $this->catalogCategoryRepository->getById($categoryId);

        $category = $this->categoryRepository->getById($parserCategoryId);
        $category->categoryId = $categoryId;

        return $this->categoryRepository->save($category);
    }
}
