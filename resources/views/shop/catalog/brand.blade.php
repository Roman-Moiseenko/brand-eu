@php
    use App\Modules\Shop\Application\DTOs\Pages\BrandIndexPageData;
    /** @var BrandIndexPageData $pageData */
@endphp
@extends('layouts.main')
@section('body', 'category')
@section('main', 'container-xl categories-page')

@section('content')
    <div class="title-page">
        <h1>Каталог товаров</h1>
    </div>
    <div class="row">
        @foreach($pageData->brands as $brand)
            @include('shop.catalog.card-brand', ['item' => $brand])
        @endforeach
    </div>

    <script type="application/ld+json" class="schemantra.com">
        {!! json_encode($pageData->schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endsection

@section('bottom-content')

@endsection
