<?php

declare(strict_types=1);

namespace App\Modules\Parser\Presentation\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckScraperApiKeyMiddleware — проверка доступа к ScraperCategoryController.
 *
 * Сравнивает заголовок X-API-Key со значением из env SCRAPER_API_KEY.
 * При отсутствии или несовпадении ключа возвращает 401.
 */
final class CheckScraperApiKeyMiddleware
{
    private const string HEADER_NAME = 'X-API-Key';

    public function handle(Request $request, Closure $next): Response
    {
        $expectedApiKey = (string) env('SCRAPER_API_KEY');
        $providedApiKey = (string) $request->header(self::HEADER_NAME);

        if ($expectedApiKey === '' || !hash_equals($expectedApiKey, $providedApiKey)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
