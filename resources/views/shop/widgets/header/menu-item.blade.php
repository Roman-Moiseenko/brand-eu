@php
    /** @var \App\Modules\Shop\Application\DTOs\CategoryTreeClientData $category */
@endphp

<li class="header-nav-item">
    <a class="header-nav-link" href="{{ route('shop.category.view', $category->slug) }}">
        {{ $category->name }}
    </a>

    @if(!empty($category->children))
        <div class="header-nav-panel">
            <div class="container-xl">
                <div class="header-nav-panel-grid">
                    @foreach($category->children as $child)
                        <div class="header-nav-column">
                            <a class="header-nav-column-title" href="{{ route('shop.category.view', $child->slug) }}">
                                {{ $child->name }}
                            </a>

                            @if(!empty($child->children))
                                <ul class="header-nav-submenu">
                                    @foreach($child->children as $sub)
                                        <li>
                                            <a class="header-nav-submenu-link" href="{{ route('shop.category.view', $sub->slug) }}">
                                                {{ $sub->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</li>
