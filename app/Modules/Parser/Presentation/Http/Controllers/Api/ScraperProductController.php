<?php

declare(strict_types=1);

namespace App\Modules\Parser\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Parser\Application\Actions\ScraperProduct\GetNextScraperProductUseCase;
use App\Modules\Parser\Application\Actions\ScraperProduct\RemoveScraperProductUseCase;
use App\Modules\Parser\Application\DTOs\ScraperProduct\ScraperProductNextRequestData;
use App\Modules\Parser\Application\DTOs\ScraperProduct\ScraperProductResultData;
use App\Modules\Parser\Application\Services\CreateProductFromScraperService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScraperProductController extends Controller
{
    public function __construct(
        private readonly GetNextScraperProductUseCase $getNextScraperProductUseCase,
        private readonly CreateProductFromScraperService $createProductFromScraperService,
        private readonly RemoveScraperProductUseCase $removeScraperProductUseCase,
    ) {}

    public function next(Request $request)
    {
        try {
            $dto = ScraperProductNextRequestData::validateAndCreate($request->all());
        } catch (ValidationException $e) {
            return response()->json($e->errors(), 422);
        }

        $data = $this->getNextScraperProductUseCase->execute($dto);

        if ($data === null) {
            return response()->json(['status' => 'done']);
        }

        return response()->json($data);
    }

    public function result(Request $request)
    {
        try {
            $dto = ScraperProductResultData::validateAndCreate($request->all());
        } catch (ValidationException $e) {
            return response()->json($e->errors(), 422);
        }

        $this->createProductFromScraperService->execute($dto);

        // После создания товара удаляем запись из очереди scraper_products
        $this->removeScraperProductUseCase->execute($dto->id);

        return response()->json(['ok']);
    }
}
