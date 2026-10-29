@php
    use App\Modules\Shop\Application\DTOs\Elements\UrlData;
    use App\Modules\Shop\Application\DTOs\Entities\CategoryRoomMainData;
    use App\Modules\Shop\Application\DTOs\Entities\CategoryRoomSecondData;
    use App\Modules\Shop\Application\DTOs\PageElements\FilterProductsData;
    /** @var FilterProductsData  $filters*/
    /** @var CategoryRoomSecondData $secondInfo */
    /** @var CategoryRoomMainData $mainInfo */
    /** @var UrlData $back */

@endphp
<div class="filters">
    <div class="mobile-close"><i class="fa-light fa-xmark"></i></div>
    <div class="base-filter">
        @if(!is_null($mainInfo))
            <div class="children">
                @if(!is_null($mainInfo->back))
                    <a href="{{ $mainInfo->back->url }}" class="heading">{{ $mainInfo->back->name }}</a>
                @endif
                <div>
                    <b>{{ $mainInfo->name }}</b>
                </div>
                @foreach($mainInfo->children as $child)
                    <div class="m-l_10 f-z_16">
                        <a href="{{ $child->url }}">{{ $child->name }}</a>
                    </div>
                @endforeach
            </div>
            <br>
        @endif



        <x-widget.numeric name="price" min-value="{{ $filters->minPrice }}" max-value="{{ $filters->maxPrice }}"
                          current-min="{{ isset($request['price']) ? $request['price'][0] : '' }}"
                          current-max="{{ isset($request['price']) ? $request['price'][1] : '' }}"
                          class="mt-3">
            Цена
        </x-widget.numeric>

        <x-widget.check name="discount" class="mt-3" checked="{{ isset($request['discount']) }}">Акция</x-widget.check>
    </div>
    <div class="attribute-filter">
        <x-widget.variant class="" caption="Бренд" id="brand">
            @foreach($filters->brands as $brand)
                <x-widget.variant-item name="brands[]" id="{{ $brand->id }}"
                                       caption="{{ $brand->name }}"
                                       image="{{ $brand->image }}"
                                       checked="{{ isset($request['brands']) ? in_array($brand->id, $request['brands']) : false }}"
                                       alt="{{ $brand->name }}"
                />
            @endforeach
        </x-widget.variant>
        <hr/>
        @if(!empty($filters->series))
            <x-widget.variant class="" caption="Серия" id="series">
                @foreach($filters->series as $series)
                    <x-widget.variant-item name="series[]" id="{{ $series->id }}"
                                           caption="{{ $series->name }}"
                                           checked="{{ isset($request['series']) ? in_array($series->id, $request['series']) : false }}"
                    />
                @endforeach
            </x-widget.variant>
            <hr/>
        @endif

        @foreach($filters->attributes as $attribute)
            <div>
                @if($attribute->isBool)
                    <x-widget.check name="a_{{ $attribute->id }}" class="mt-2" value="{{ $attribute->id }}"
                                    checked="{{ isset($request['a_' . $attribute->id]) }}">
                        {{ $attribute->name }}</x-widget.check>
                    <hr/>
                @endif
                @if($attribute->isNumeric)
                    <x-widget.numeric name="a_{{ $attribute->id }}" min-value="{{ $attribute->min }}"
                                      max-value="{{ $attribute->max }}"
                                      current-min="{{ isset($request['a_' . $attribute->id]) ? $request['a_' . $attribute->id][0] : '' }}"
                                      current-max="{{ isset($request['a_' . $attribute->id]) ? $request['a_' . $attribute->id][1] : '' }}"
                                      class="mt-3">
                        {{ $attribute->name }}
                    </x-widget.numeric>
                    <hr/>
                @endif
                @if($attribute->isVariant)
                    <x-widget.variant class="" caption="{{ $attribute->name }}" id="{{ $attribute->id }}">
                        @foreach($attribute->variants as $variant)
                            <x-widget.variant-item name="a_{{ $attribute->id }}[]" id="{{ $variant->id }}"
                                                   caption="{{ $variant->name }}"
                                                   image="{{ $variant->image }}"
                                                   checked="{{ isset($request['a_' . $attribute->id]) ? in_array($variant->id, $request['a_' . $attribute->id]) : false }}"
                                                   alt="{{ $variant->name }}"
                            />
                        @endforeach
                    </x-widget.variant>
                    <hr/>
                @endif

            </div>
        @endforeach
    </div>
    <div class="buttons-filter">
        <button class="btn btn-black w-auto">Применить</button>
        <button id="clear-filter" class="btn btn-black w-auto" type="button">Сбросить</button>

    </div>
</div>
