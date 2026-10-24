@extends('layouts.blank')
@section('title', 'Страница 403')
@section('main', 'error')

@section('breadcrumbs')
    &nbsp;
@endsection

@section('content')
<div class="container-xl mt-5">
    <h1>Ай-ай-ай,Вам сюда нельзя! Ошибка доступа</h1>
    <div class="fs-5">
        Мы не нашли страницу по Вашему запросу. Возможно она была удалена, либо никогда не существовала.
    </div>
    <div class="fs-5">
        Но, Вы можете выбрать товар из наличия в нашем каталоге или заказать товары Икеа на заказ с доставкой
    </div>
</div>

<div class="container-xl my-5">

</div>
@endsection
