<?php
declare(strict_types=1);

namespace App\Modules\Shop\Presentation\Http\Controllers\Web;


use App\Modules\Shop\Application\Queries\Category\CategoryIndexQuery;
use App\Modules\Shop\Application\Queries\Category\CategoryPageQuery;
use App\Modules\Shop\Application\Queries\Category\NoveltyPageQuery;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

class CatalogController extends ShopAbstractController
{
    public function __construct(
        private readonly CategoryPageQuery $categoryPageQuery,
        private readonly NoveltyPageQuery $noveltyPageQuery,
        private readonly CategoryIndexQuery $categoryIndexQuery,
    )
    {
    }

    public function index(Request $request): Factory|View|Application|null
    {
        $data = $this->categoryIndexQuery->execute();

        return view('shop.catalog.category', [
            'pageData' => $data,
        ]);
    }

    public function view(Request $request, $slug): View|Factory|\Illuminate\View\View
    {
        $start = microtime(true);

        $data = $this->categoryPageQuery->execute($slug, $request->all(), $this->getClient($request));

        $durationMs = (microtime(true) - $start) * 1000;
        Log::info('CategoryPageQuery execution time', ['milliseconds' => $durationMs]);

        if (is_null($data)) abort(404, 'Категория не найдена');

        return view('shop.product.index', [
            'pageData' => $data,
            'request' => $request->all(),
        ]);
    }

    public function novelty(Request $request): View|Factory|\Illuminate\View\View
    {
        $data = $this->noveltyPageQuery->execute($request->all(), $this->getClient($request));

        return view('shop.product.novelty', [
            'pageData' => $data,
            'request' => $request->all(),
        ]);
    }
}
