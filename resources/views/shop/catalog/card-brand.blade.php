@php
    use App\Modules\Shop\Application\DTOs\Entities\BrandData;
    /** @var BrandData $item */
@endphp
<div class="col-6 col-sm-6 col-md-4 col-lg-3">
    <div class="catalog-card">
        <a href="{{ route('shop.brand.view', $item->slug) }}">
            <div>
                <img
                    src="{{ (empty($item->image)) ? '\images\no-image.jpg' : $item->image }}"
                    alt="{{ $item->name }}">
                <span>{{ $item->name }}</span>
            </div>
        </a>
        <div>
            {!! $item->description !!}
        </div>
    </div>
</div>
