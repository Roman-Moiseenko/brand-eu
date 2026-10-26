@php
    /** @var App\Modules\Shop\Application\DTOs\Menu\MenuData[] $menus */
@endphp
<footer id="footer" class="p-t_50 bg-black">
    <div class="container-xl">
        <div class="row justify-content-between">
            <div class="col-12 col-md-12 col-lg-5">
                <div class="heading f-w_600 f-z_23 m-b_20">SPORT SHOPPER</div>
                <p>
                    Проффесиональный спортивный инвентарь, обувь, одежда и акссесуары известных мировых брендов с ппоставкой из Европы
                </p>
                <p>тел. <a href="tel:89994248818">8 (999) 424-88-18</a>
                    <br>
                    <a href="mailto:info@sport-shopper.ru">info@sport-shopper.ru</a> - Email организации
                </p>

                <p>ООО «Норди Хоум», ИНН 3906396090, КПП 390601001, ОГРН 1203900013602, 236023 Калининград, ул Советский
                    проспект, 103А корпус 1</p>

                <p>
                    Предоставляемая информация носит информационный характер. Не является публичной офертой,
                    определяемой положениями статьи 437 ГК РФ.
                    Все права на публикуемые здесь материалы принадлежат ООО «Норди Хоум». При использовании материалов,
                    активная ссылка на сайт sport-shopper.ru обязательна.
                </p>
            </div>
            <div class="col-12 col-md-12 col-lg-3"></div>
            <div class="col-12 col-sm-6 col-md-6 col-lg-4">
                @if(isset($menus['footer']))
                    <ul class="footer-menu">
                        @foreach($menus['footer']->items as $item)
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
        <p>2022 - {{date('Y')}} | Разработано <a href="https://koenigs.ru" title="Разработка CRM и интернет-магазинов"
                                                 target="_blank">Веб-студия Web39</a></p>
    </div>

</footer>
