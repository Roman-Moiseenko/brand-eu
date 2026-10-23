(function () {

    //Аккордион
    $('.accordion_1 .accordion-heading').on('click', function (){
        let thisContentBlock = $(this).parent().find('.accordion-text');
        if(thisContentBlock.hasClass('active')) {
            thisContentBlock.removeClass('active')
        }
        else {
            thisContentBlock.addClass('active')
        }
    });
    var acc = document.getElementsByClassName("accordion-heading");
    var i;
    for (i = 0; i < acc.length; i++) {
        acc[i].addEventListener("click", function() {
            this.classList.toggle("active");

        });
    }


    //FancyBox
/*
    const galleryItems = window.productGalleryData;
    console.log(galleryItems)
    // Клик по главному изображению – открываем Fancybox
    $('#main-image-product').on('click', function (e) {
        e.preventDefault();
        const currentIndex = $('#main-image-product').data('index') || 0;

        Fancybox.show(galleryItems, {
            startIndex: currentIndex,

            infinite: true,
            Thumbs: {
                autoStart: false,
            },
            caption: function (fancybox, slide) {
                return slide.caption || '';
            },
        });
    });
    */
    let main = $('main');
    /** СТРАНИЦА ТОВАРА **/
    if (main.hasClass('product-page') || main.hasClass('ikea-product')) {
        let sliderImages = $('.slider-image-product');
        let mainImage = $('#main-image-product');
        sliderImages.on('mouseover', function () {
            mainImage.attr('src', $(this).data('image'));
            mainImage.attr('data-index', $(this).data('index'));
        });
    }

    /** ВЫБОР РАЗМЕРА В КАРТОЧКЕ ТОВАРА **/
    let $productSizes = $('.product-sizes');
    if ($productSizes.length > 0) {
        let $toCart = $('.product-page .to-cart');
        let twoAxes = String($productSizes.data('two-axes')) === '1';

        function setSelectedSize(sizeId) {
            $productSizes.attr('data-selected-size-id', sizeId ? String(sizeId) : '');
            if ($toCart.length > 0) {
                $toCart.attr('data-options', sizeId ? String(sizeId) : '');
            }
            if (sizeId) {
                $productSizes.find('.product-sizes-error').hide();
            }
        }

        function clearSizeSelection() {
            $productSizes.find('.product-size-btn').removeClass('active');
            setSelectedSize(null);
        }

        function updateSizeUrl(sizeId) {
            let url = new URL(window.location.href);
            if (sizeId) {
                url.searchParams.set('size', String(sizeId));
            } else {
                url.searchParams.delete('size');
            }
            window.history.replaceState({}, '', url.toString());
        }

        // Клик по размеру
        $productSizes.on('click', '.product-size-btn', function () {
            let $btn = $(this);
            let sizeId = $btn.data('product-size-id');
            $productSizes.find('.product-size-btn').removeClass('active');
            $btn.addClass('active');
            setSelectedSize(sizeId);
            updateSizeUrl(sizeId);
        });

        if (twoAxes) {
            let $widthBtns = $productSizes.find('.product-width-btn');
            let $sizeLists = $productSizes.find('.product-sizes-list[data-width-value]');

            function showWidth(widthValue) {
                $sizeLists.each(function () {
                    let $list = $(this);
                    if (String($list.data('width-value')) === String(widthValue)) {
                        $list.show();
                    } else {
                        $list.hide();
                    }
                });
                $widthBtns.each(function () {
                    $(this).toggleClass('active', String($(this).data('width-value')) === String(widthValue));
                });
            }

            // Выбор ширины: размеры показываются только для выбранной ширины
            $widthBtns.on('click', function () {
                let widthValue = $(this).data('width-value');
                clearSizeSelection();
                updateSizeUrl(null);
                showWidth(widthValue);
            });

            // По умолчанию показываем размеры первой ширины
            if ($widthBtns.length > 0) {
                showWidth($widthBtns.first().data('width-value'));
            }
        }

        // Предвыбор размера из URL (?size=productSizeId)
        let urlSize = new URL(window.location.href).searchParams.get('size');
        if (urlSize) {
            let $sizeBtn = $productSizes.find('.product-size-btn[data-product-size-id="' + urlSize + '"]');
            if ($sizeBtn.length > 0) {
                if (twoAxes) {
                    let $parentList = $sizeBtn.closest('.product-sizes-list[data-width-value]');
                    if ($parentList.length > 0) {
                        showWidth($parentList.data('width-value'));
                    }
                }
                $productSizes.find('.product-size-btn').removeClass('active');
                $sizeBtn.addClass('active');
                setSelectedSize(urlSize);
            }
        }

        // Валидация: без выбранного размера кнопка «В корзину» не срабатывает
        if ($toCart.length > 0) {
            $toCart.on('click', function (e) {
                let selected = $productSizes.attr('data-selected-size-id');
                if (!selected) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    $productSizes.find('.product-sizes-error').show();
                    return false;
                }
            });
        }
    }

})();



