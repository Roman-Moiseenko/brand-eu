@php
    /** @var App\Modules\Shop\Application\DTOs\Menu\MenuData[] $menus */
/** @var  App\Modules\Shop\Application\DTOs\Menu\ContactData[] $contacts*/

@endphp

<header>

    <nav class="menu-bottom navbar navbar-expand-md navbar-light bg-white">
        <div class="menu-container container-xl">
            <div class="menu-bottom-catalog d-flex">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <img src="/images/icons/brand.svg" alt="Euro Sport Brand" class="img-fluid img-logo">
                    <div class="h-city-text">склады находятся в г.Калининград</div>
                </a>
                <ul>
                    @foreach($categoryTree as $category)
                        @include('shop.widgets.header.menu-item', ['category' => $category])
                    @endforeach
                </ul>
            </div>
            <div class="menu-bottom-search flex-grow-1">
                @include('shop.widgets.header.search')
            </div>

            <div class="menu-bottom-profile">
                <ul class="navbar-nav ms-auto">
                    @client
                    <li class="nav-item">
                        <a class="nav-link d-flex flex-column text-center" href="{{ route('cabinet.view') }}">
                            <i class="fa-light fa-user-vneck fs-4"></i>
                            <span class="fs-7">Кабинет</span>
                        </a>
                    </li>
                    @else
                        <li class="nav-item">
                            <a id="login" class="nav-link d-flex flex-column text-center" href="#"
                               data-bs-toggle="modal" data-bs-target="#login-popup">
                                <i class="fa-light fa-user-vneck fs-4"></i>
                                <span class="fs-7">Войти</span>
                            </a>
                        </li>
                        @endclient

                        <li class="nav-item">
                            <livewire:header.wish/>
                        </li>

                        <!-- <li class="nav-item">
            <a class="nav-link d-flex flex-column text-center" href="{{ route('cabinet.order.index') }}"
               @notclient
                        data-bs-toggle="modal" data-bs-target="#login-popup"
@endnotclient
                        >
                            <i class="fa-sharp fa-light fa-box-open fs-4"></i>
                            <span class="fs-7">Заказы</span>
                        </a>
                    </li> -->

                        <li class="nav-item">
                            <livewire:header.cart :client-id="$client?->id ?? null"/>
                        </li>
                </ul>
            </div>
        </div>
    </nav>


    <nav class="menu-mobile">
        <ul class="menu-list">
            <li class="menu-item">
                <a href="{{ route('shop.home') }}" class="nav-link d-flex flex-column text-center">
                    <i class="fa-light fa-house fs-3"></i>
                    <span class="fs-8">Главная</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{ route('shop.category.index') }}" class="nav-link d-flex flex-column text-center">
                    <i class="fa-light fa-folder-magnifying-glass fs-3"></i>
                    <span class="fs-8">Каталог</span>
                </a>
            </li>
            <li class="menu-item">
                <livewire:header.cart-mobile/>
            </li>

            <li class="menu-item">
                <a href="{{ route('shop.ikea.index') }}" class="nav-link d-flex flex-column text-center">
                    <!--img src="/images/ikea.svg" style="height: 40px;"-->
                    <!-- <i class="fa-light fa-lightbulb fs-3"></i> -->
                    <!--i class="fa-sharp fa-light fa-box-open fs-3"></i-->
                    <span class="fs-9">ИКЕА</span>
                    <span class="fs-8">под заказ</span>
                </a>
            </li>


            @client
            <li class="menu-item">
                <a class="nav-link d-flex flex-column text-center" href="{{ route('cabinet.view') }}">
                    <i class="fa-light fa-user-vneck fs-3"></i>
                    <span class="fs-8">Кабинет</span>
                </a>
            </li>


            @else
                <li class="menu-item">
                    <a id="login" class="nav-link d-flex flex-column text-center" href="#" data-bs-toggle="modal"
                       data-bs-target="#login-popup">
                        <i class="fa-light fa-user-vneck fs-3"></i>
                        <span class="fs-8">Войти</span>
                    </a>
                </li>
                @endclient
        </ul>
    </nav>

</header>


