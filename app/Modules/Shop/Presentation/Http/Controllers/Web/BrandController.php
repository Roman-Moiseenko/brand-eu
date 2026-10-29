<?php
declare(strict_types=1);

namespace App\Modules\Shop\Presentation\Http\Controllers\Web;

use App\Modules\Shop\Application\Queries\Brand\BrandIndexQuery;
use App\Modules\Shop\Application\Queries\Brand\BrandPageQuery;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BrandController extends ShopAbstractController
{


    public function __construct(
        private readonly BrandPageQuery $brandPageQuery,
        private readonly BrandIndexQuery $brandIndexQuery,
    )
    {
    }
    public function index(Request $request)
    {
        $data = $this->brandIndexQuery->execute($this->getClient($request));

        return view('shop.catalog.brand', [
            'pageData' => $data,
            'request' => $request->all(),
        ]);
    }

    public function view(Request $request, string $slug): View|Factory|\Illuminate\View\View
    {
        $data = $this->brandPageQuery->execute($slug, $request->all(),
            $this->getClient($request));
        if (is_null($data)) abort(404, 'Бренд не найден');
        return view('shop.product.index', [
            'pageData' => $data,
            'request' => $request->all(),
        ]);
    }
}
