<?php

declare(strict_types=1);

namespace App\Modules\Parser\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Parser\Application\Actions\ScraperCategory\GetNextScraperCategoryUseCase;
use App\Modules\Parser\Application\DTOs\ScraperCategory\ScraperCategoryNextRequestData;
use App\Modules\Parser\Application\DTOs\ScraperCategory\ScraperCategoryResultData;
use App\Modules\Parser\Application\Services\HandleScraperCategoryResultService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScraperCategoryController extends Controller
{
    public function __construct(
        private readonly GetNextScraperCategoryUseCase $getNextScraperCategoryUseCase,
        private readonly HandleScraperCategoryResultService $handleScraperCategoryResultService,
    ) {}

    public function next(Request $request)
    {
        try {
            $dto = ScraperCategoryNextRequestData::validateAndCreate($request->all());
        } catch (ValidationException $e) {
            return response()->json($e->errors(), 422);
        }

        $data = $this->getNextScraperCategoryUseCase->execute($dto);

        if ($data === null) {
            return response()->json(['status' => 'done']);
        }

        return response()->json($data);
    }

    public function result(Request $request)
    {
        \Log::info('result', $request->all());
        try {
            $dto = ScraperCategoryResultData::validateAndCreate($request->all());
        } catch (ValidationException $e) {
            return response()->json($e->errors(), 422);
        }

        $this->handleScraperCategoryResultService->execute($dto);

        return response()->json(['ok']);
    }
}
