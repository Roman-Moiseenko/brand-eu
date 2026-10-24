<?php

namespace App\Modules\Shop\Presentation\Http\ViewComposers;

use App\Modules\Shop\Infrastructure\Persistence\Query\BrandPageQueryRepository;
use Illuminate\View\View;

readonly class BrandComposer
{
    public function __construct(
        private BrandPageQueryRepository $query
    ) {}

    public function compose(View $view): void
    {

        $view->with('brandsMenu', $this->query->getBrandsForMenu());
    }
}
