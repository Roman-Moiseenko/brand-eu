@php
    /** @var App\Modules\Shop\Application\DTOs\Menu\MenuData[] $menus */
@endphp
<footer id="footer" class="p-t_50 p-b_50 bg-black">
    <div class="container-xl">
        <div class="row justify-content-between">
            <div class="col-12 col-md-12 col-lg-5">
                <div class="heading f-w_600 f-z_23 m-b_20">ЕВРО СПОРТ БРЕНД</div>

            </div>
            <div class="col-12 col-md-12 col-lg-3"></div>
            <div class="col-12 col-sm-6 col-md-6 col-lg-2">
                <div class="heading f-w_600 m-b_20">МЕНЮ</div>
                @if(isset($menus['menu-footer01']))
                <ul class="footer-menu">
                    @foreach($menus['menu-footer01']->items as $item)
                        <li><a href="{{ $item->url }}">{{ $item->name }}</a></li>
                    @endforeach
                </ul>
                @else
                Меню не найдено
                @endif
            </div>
            <div class="col-12 col-sm-6 col-md-6 col-lg-2">
                <div class="heading f-w_600 m-b_20">ДЛЯ КЛИЕНТА</div>
                @if(isset($menus['menu-footer02']))
                <ul class="footer-menu">
                    @foreach($menus['menu-footer02']->items as $item)
                        <li><a href="{{ $item->url }}">{{ $item->name }}</a></li>
                    @endforeach
                </ul>
                @else
                    Меню не найдено
                @endif
            </div>
        </div>
    </div>
    <div class="about pt-4 pb-3 text-center">
        <p>2022 - {{date('Y')}} | Разработано <a href="https://koenigs.ru" title="Разработка CRM и интернет-магазинов" target="_blank">Веб-студия Web39</a></p>
    </div>

</footer>
