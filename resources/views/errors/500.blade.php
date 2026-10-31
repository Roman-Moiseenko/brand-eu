@extends('layouts.blank')
@section('title', 'Страница 500')
@section('main', 'error')

@section('breadcrumbs')
    &nbsp;
@endsection

@section('content')

    <div class="container-xl mt-5">
        <h1>Страница не найдена. Ошибка 500</h1>
        <div class="fs-5">
            Какая-то ошибка, мы сами вообще не вкурсе что сломалось.
        </div>
        <div class="fs-5">
            Но, в любом случае мы все починим. Спасибо за внимание
        </div>

        <div class="row align-items-center">
            <div class="col-lg-6 t-a_center">
                <img src="/images/icons/logo.png" alt="Обувь, одежда и инвентарь из Европы">
            </div>
            <div class="col-lg-5">
                <h3 class="heading m-b_20">Какая-то ошибка, мы сами вообще не вкурсе что сломалось.</h3>
                <div class="m-b_20 f-z_17">Но, в любом случае мы все починим. Спасибо за внимание.<br>Пожалуйста, не уходите: воспользуйтесь поиском или перейдите в наш каталог.</div>
                <div><section id="block-13" class="widget widget_block"></section></div>
                <div class="d-flex justify-content-between">
                    <div><a href="/" class="btn btn-white-b t-t_uppercase m-t_20">На главную</a></div>
                    <div><a href="/shop/" class="btn btn-orange m-t_20">Смотреть каталог</a></div>
                </div>
            </div>
        </div>
    </div>


@endsection
