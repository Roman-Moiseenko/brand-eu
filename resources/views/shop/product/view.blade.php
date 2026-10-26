@php
    /** @var \App\Modules\Shop\Application\DTOs\Pages\ProductViewPageData $pageData */

    $product = $pageData->product;
    $galleryItems = [];
    foreach ($product->images as $img) {
        $galleryItems[] = [
            'src' => $img->full,
            'caption' => $img->alt ?? '',
        ];
    }
@endphp

@extends('layouts.main')
@section('body', 'product')
@section('main', 'container-xl product-page')
@section('bottom-class', '')

@section('content')
    <span class="e-detail" data-product="{{ $product->id }}"></span>

    <div class="box-card">
        <div class="row">
            <div class="col-lg-6">
                <div class="view-image-product">
                    @if(!empty($product->images))
                        <img id="main-image-product" src="{{ $product->images[0]->src }}"
                             style="width: 100%; cursor: pointer" data-index="0">

                        @if($product->only_on_order)
                            <div class="product-label on-order">
                                <span>Только под заказ</span>
                            </div>
                        @endif

                        @if($product->is_new)
                            <div class="product-label new"><span>NEW</span></div>
                        @elseif($product->promotion->has && $product->promotion->showTag)
                            @if($product->promotion->showDiscount)
                                <div class="promotion-label discount">
                                    <span>- {{ price($product->price - $product->promotion->price) }}</span>
                                </div>
                            @endif
                            <div
                                class="product-label promotion {{ $product->promotion->color }} {{ $product->promotion->position }}">
                                <span>{{ $product->promotion->text }}</span>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="slider-images-product owl-carousel owl-theme p-3" data-responsive="[3,6,9]">
                    @foreach($product->images as $index => $photo)
                        <img src="{{ $photo->mini }}" data-image="{{ $photo->src }}"
                             class="slider-image-product" alt="{{ $photo->alt }}" data-index="{{ $index }}">
                    @endforeach
                </div>

            </div>
            <div class="col-lg-6">
                <div class="view-info-product">
                    <h1>{{ $product->name }}</h1>
                    <div class="view-rating">
                        <div>
                            @include('shop.widgets.to-wish', ['product' => $product])
                        </div>
                        <div>
                            <a href="#reviews" title="Отзывы реальных покупателей"
                               aria-label="Отзывы реальных покупателей">{{ $product->count_reviews }}</a>
                            @include('shop.widgets.stars', ['rating' => $product->rating, 'show_number' => true])
                        </div>
                    </div>
                    <div class="product-code">Артикул: <span>{{ $product->code }}</span></div>
                    <div class="view-brand f-z_16 m-t_10">
                        @if(empty($product->brandLogo))
                            <span><b>Бренд:</b> {{ $product->brandName }}</span>
                        @else
                            <img src="{{ $product->brandLogo }}"
                                 alt="{{ $product->brandName  }}" title="{{ $product->brandName }}">
                        @endif

                    </div>
                    <div class="product-options-link">
                        <a href="#specifications">Характеристики</a>
                        <a href="#description">Описание</a>
                        <a href="#с-этим-товаром-часто-покупают">Рекомендуем</a>
                        <a href="#доставка-по-всей-россии">Доставка</a>
                        @if(!empty($product->care))
                            <a href="#care">Уход</a>
                        @endif
                    </div>
                    <div class="price-brand-block">
                        <div class="view-price">
                            @if($product->is_sale)
                                @if(!$product->promotion->has)
                                    @if($product->price_previous > $product->price)
                                        <div class="comment">* Цена на товар снижена</div>
                                        <span class="discount-price">{{ price($product->price) }}</span>
                                        <span class="base-price">{{ price($product->price_previous) }}</span>
                                    @else
                                        {{ price($product->price) }}
                                    @endif
                                @else
                                    <div class="comment">* Цена по акции {{ $product->promotion->name }}</div>
                                    <span class="discount-price">{{ price($product->promotion->price) }}</span>
                                    <span class="base-price">{{ price($product->price) }}</span>
                                @endif
                            @else
                                {{ price($product->price) }}
                            @endif
                            @if(!empty($product->sizes))
                                <div class="product-sizes" data-two-axes="{{ $product->twoAxes ? '1' : '0' }}">
                                    <div class="product-sizes-error" style="display: none;">Пожалуйста выберите размер</div>

                                    @if(!$product->twoAxes)
                                        <div class="product-sizes-list">
                                            @foreach($product->sizes as $size)
                                                <button type="button" class="product-size-btn"
                                                        data-product-size-id="{{ $size->productSizeId }}">{{ $size->value }}</button>
                                            @endforeach
                                        </div>
                                    @else
                                        @foreach($product->sizes as $width)
                                            <div class="product-sizes-list" data-width-value="{{ $width->value }}">
                                                @foreach($width->items as $size)
                                                    <button type="button" class="product-size-btn"
                                                            data-product-size-id="{{ $size->productSizeId }}">{{ $size->value }}</button>
                                                @endforeach
                                            </div>
                                        @endforeach
                                        <div class="product-widths">
                                            @foreach($product->sizes as $width)
                                                <button type="button" class="product-width-btn"
                                                        data-width-value="{{ $width->value }}">
                                                    {{ $width->label && $width->label !== $width->value ? $width->label . ' (' . $width->value . ')' : $width->value }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif


                            <div class="count-product">
                                @if($product->is_sale)
                                    @if($product->quantity > 0)
                                        Товар в наличии
                                    @else
                                        Только под заказ
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="product-card-to-cart">
                        @if($product->is_sale)
                            <button class="to-cart btn btn-black e-add{{ $product->in_cart ? ' in-cart' : '' }}"
                                    data-product="{{ $product->id }}" data-options="{{ '' }}">
                                {{ $product->in_cart ? 'В Корзине' : 'В Корзину' }}
                            </button>
                        @else
                            <button type="button" class="btn btn-secondary" disabled>Снят с продажи</button>
                        @endif
                    </div>
                    <!--h2 class="f-z_23 m-b_30 m-t_30" id="характеристики">Характеристики</h2-->
                    <div class="short-description">
                        {!! $product->short !!}
                    </div>
                    @include('shop.product.__modification', [
                        'modification' => $pageData->modification,
                        'current_id' => $product->id,
                    ])
                    @include('shop.product.__related', ['related' => $pageData->related])



                </div>
            </div>
        </div>

    </div>

    @include('shop.product._bonus', ['bonus' => $pageData->bonus])

    @include('shop.product._series', ['series' => $pageData->series])

    <div class="box-card">
        <div id="описание"></div>
        <h2 id="description">Описание</h2>
        {!! $product->description !!}
    </div>


    @include('shop.product._attribute', [
        'productAttributes' => $pageData->attributes,
    ])

    @include('shop.product._equivalent', ['equivalents' => $pageData->equivalents])

    @include('shop.product._reviews', ['reviews' => $pageData->reviews])

    <!-- <section class="related-products">
        <h2 id="с-этим-товаром-часто-покупают">С этим товаром часто покупают</h2>
        <div id="" class="owl-carousel owl-theme slider-images-product">
            <div>
                тут товар 1
            </div>
            <div>
                тут товар 1
            </div>
            <div>
                тут товар 1
            </div>
            <div>
                тут товар 1
            </div>
            <div>
                тут товар 1
            </div>
            <div>
                тут товар 1
            </div>
            <div>
                тут товар 1
            </div>

        </div>
    </section> -->

    @include('shop.product._delivery')

    <script type="application/ld+json" class="schemantra.com">
        {!! json_encode($pageData->schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- Передаём данные из PHP в JavaScript --}}
    <script>
        window.productGalleryData = @json($galleryItems);
    </script>

    {{-- Подключаем наш отдельный JS-файл --}}
    @vite('resources/js/brand-eu/product.js')

@endsection

@section('bottom-content')

@endsection

