<?php

namespace App\Modules\Parser\Application\Actions\Category;

use App\Modules\Base\Service\TranslateService;
use App\Modules\Parser\Application\DTOs\Category\ParserCategoryCreateData;
use App\Modules\Parser\Domain\Entities\ParserCategoryEntity;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Shared\Domain\Entities\UserPermission;
use App\Modules\Shared\Domain\Exceptions\AccessDeniedException;
use App\Modules\Shared\Domain\ValueObjects\Slug;

readonly class CreateParserCategoryUseCase
{
    public function __construct(
        private ParserCategoryRepositoryInterface $categoryRepository,
        private TranslateService $translateService,
    )
    {
    }

    public function execute(ParserCategoryCreateData $dto): ParserCategoryEntity
    {
        $category = $this->categoryRepository->findByCode($dto->code);


        if (!is_null($category)) return $category;

        $category = new ParserCategoryEntity(
            name: $dto->name,
            code: $dto->code,
            brandId: $dto->brandId,
            parentId: $dto->parentId ?: null,
        );
        $category->url = $dto->url;
        $category->nameRu = $this->translateService->translate($dto->name, $dto->lang);

        return $this->categoryRepository->save($category);
    }
}
