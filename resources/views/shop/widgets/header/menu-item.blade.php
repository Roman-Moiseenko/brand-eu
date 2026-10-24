@php
    /** @var \App\Modules\Shop\Application\DTOs\CategoryTreeClientData $category */
@endphp

<li class="nav-category">
    <a href="{{ route('shop.category.view', $category->slug) }}">
        {{ $category->name }}
    </a>

    @if(!empty($category->children))
        <div class="nav-category__panel">
            <div class="container-xl">
                <div class="nav-category__grid">
                    @foreach($category->children as $child)
                        <div class="nav-category__column">
                            <a href="{{ route('shop.category.view', $child->slug) }}">
                                {{ $child->name }}
                            </a>

                            @if(!empty($child->children))
                                <ul>
                                    @foreach($child->children as $sub)
                                        <li>
                                            <a href="{{ route('shop.category.view', $sub->slug) }}">
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
