<?php

declare(strict_types=1);

namespace App\Modules\Parser\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Parser\Application\Actions\ScraperAvailable\GetNextScraperAvailableUseCase;
use App\Modules\Parser\Application\Actions\ScraperAvailable\HandleScraperAvailableResultUseCase;
use App\Modules\Parser\Application\DTOs\ScraperAvailable\ScraperAvailableNextRequestData;
use App\Modules\Parser\Application\DTOs\ScraperAvailable\ScraperAvailableResultData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScraperAvailableController extends Controller
{
    public function __construct(
        private readonly GetNextScraperAvailableUseCase $getNextScraperAvailableUseCase,
        private readonly HandleScraperAvailableResultUseCase $handleScraperAvailableResultUseCase,
    ) {}

    public function next(Request $request)
    {
        try {
            $dto = ScraperAvailableNextRequestData::validateAndCreate($request->all());
        } catch (ValidationException $e) {
            return response()->json($e->errors(), 422);
        }

        $data = $this->getNextScraperAvailableUseCase->execute($dto);

        if ($data === null) {
            return response()->json(['status' => 'done']);
        }

        return response()->json($data);
    }

    public function result(Request $request)
    {
        try {
            $dto = ScraperAvailableResultData::validateAndCreate($request->all());
        } catch (ValidationException $e) {
            return response()->json($e->errors(), 422);
        }

        $this->handleScraperAvailableResultUseCase->execute($dto);

        return response()->json(['ok']);
    }
}
