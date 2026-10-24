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
                    <div class="nav-category__brands">
                        <span class="nav-category__brands-title">Бренды</span>

                        <ul>
                            @foreach($brandsMenu ?? [] as $brand)
                                <li>
                                    <a href="{{ route('shop.category.view', ['slug' => $category->slug, 'brands[]' => $brand->id]) }}">
                                        {{ $brand->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="nav-category__columns">
                        @foreach(array_chunk($category->children, 5) as $row)
                            <div class="nav-category__row">
                                @foreach($row as $child)
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
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</li>
