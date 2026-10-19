<?php

namespace App\Modules\Parser\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Parser\Application\Actions\Brand\ListParserBrandQuery;
use App\Modules\Parser\Application\Actions\Category\IndexParserCategoryUseCase;
use App\Modules\Parser\Application\Actions\Category\ListParserCategoryByBrandQuery;
use App\Modules\Parser\Application\Actions\Category\SetParserCategoryCatalogUseCase;
use App\Modules\Parser\Application\Actions\Category\SetParserCategorySizeGridUseCase;
use App\Modules\Parser\Application\Actions\Category\ViewParserCategoryUseCase;
use App\Modules\Parser\Application\Actions\Product\ListAllProductByCategoryUseCase;
use App\Modules\Parser\Application\DTOs\Category\ParserCategoryIndexData;
use App\Modules\Parser\Application\Services\ToggleCategoryWithProductsService;
use App\Modules\Parser\Domain\Entities\ParserCategoryEntity;
use App\Modules\Parser\Infrastructure\Jobs\old\LoadProductsIkeaJob;
use App\Modules\Parser\Infrastructure\Models\ParserCategory;
use App\Modules\Shared\Domain\Entities\UserPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryParserController extends Controller
{

    public function __construct(
        private readonly IndexParserCategoryUseCase $indexParserCategoryUseCase,
        private readonly ToggleCategoryWithProductsService $toggleCategoryWithProductsService,
        private readonly ViewParserCategoryUseCase $viewParserCategoryUseCase,
        private readonly ListAllProductByCategoryUseCase $listAllProductByCategoryUseCase,
        private readonly ListParserBrandQuery $listParserBrandQuery,
        private readonly ListParserCategoryByBrandQuery $listParserCategoryByBrandQuery,
        private readonly SetParserCategoryCatalogUseCase $setParserCategoryCatalogUseCase,
        private readonly SetParserCategorySizeGridUseCase $setParserCategorySizeGridUseCase,
    )
    {
    }

    public function index(Request $request, UserPermission $userPermission): Response
    {
        $brands = $this->listParserBrandQuery->execute($userPermission);

        $selectedBrand = $request->query('brand');
        $selectedBrand = is_string($selectedBrand) && $selectedBrand !== '' ? $selectedBrand : null;

        $categories = $this->listParserCategoryByBrandQuery->execute($selectedBrand, $userPermission);

        return Inertia::render('Parser/Category/Index', [
            'brands' => $brands,
            'categories' => array_map(
                fn(ParserCategoryEntity $category) => ParserCategoryIndexData::fromEntity($category),
                $categories,
            ),
            'selectedBrand' => $selectedBrand,
        ]);
    }

    public function show(ParserCategory $category_parser, UserPermission $userPermission): Response
    {
        $category = $this->viewParserCategoryUseCase->execute($category_parser->id, $userPermission);
        return Inertia::render('Parser/Category/Show', [
            'category' => ParserCategoryIndexData::fromEntity($category),
        ]);
    }

    public function toggle(int $id, UserPermission $userPermission): RedirectResponse
    {
        $message = $this->toggleCategoryWithProductsService->execute($id, $userPermission);

        return redirect()->back()->with('success', $message);
    }

    public function parser_products(ParserCategory $category_parser, UserPermission $userPermission): JsonResponse
    {
        LoadProductsIkeaJob::dispatch($category_parser->code, $userPermission);
        return response()->json(['message' => 'Поставлено в очередь']);
    }

    public function setCatalog(ParserCategory $category_parser, Request $request, UserPermission $userPermission): JsonResponse
    {
        $categoryId = $request->input('category_id');
        $categoryId = is_numeric($categoryId) ? (int) $categoryId : null;

        $this->setParserCategoryCatalogUseCase->execute(
            $category_parser->id,
            $categoryId,
            $userPermission,
        );

        return response()->json(['message' => 'Категория каталога сохранена']);
    }

    public function setSizeGrid(ParserCategory $category_parser, Request $request, UserPermission $userPermission): JsonResponse
    {
        $sizeGridId = $request->input('size_grid_id');
        $sizeGridId = is_numeric($sizeGridId) ? (int) $sizeGridId : null;

        $this->setParserCategorySizeGridUseCase->execute(
            $category_parser->id,
            $sizeGridId,
            $userPermission,
        );

        return response()->json(['message' => 'Размерная сетка сохранена']);
    }

    public function products(int $id, Request $request): JsonResponse
    {
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 15);
        $list = $this->listAllProductByCategoryUseCase->execute($id, $perPage, $page);

        return response()->json($list, \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }

}
