import jQuery from "jquery";

window.$ = jQuery;

(function () {
    "use strict";
    console.log("Slider")
    //Карусели
    let optionsSliderBase = {
        rtl: false,
        startPosition: 0,
        items: 1,
        autoplay: true, //
        smartSpeed: 1500, //Время движения слайда
        autoplayTimeout: 5000, //Время смены слайда
        autoplayHoverPause: true,
        margin: 10,
        loop: true,
        dots: true,
        nav: false,
        navText: ['<i class="fa fa-chevron-left"></i>', '<i class="fa fa-chevron-right"></i>'],
        singleItem: true,
        transitionStyle: "fade",
        touchDrag: true,
        mouseDrag: true,
        dotsEach: 1,
        responsive: {}
    };

    function initOwlAria($slider) {
        $slider.find('.owl-prev').attr('aria-label', 'Предыдущий слайд');
        $slider.find('.owl-next').attr('aria-label', 'Следующий слайд');
        $slider.find('.owl-dot').each(function (index) {
            $(this).attr('aria-label', 'Перейти к слайду ' + (index + 1));
        });
    }

    if (document.querySelectorAll('.widget-slider-common').length) {
        $('.widget-slider-common').each(function () {
            let sliderWidget = $(this);
            let optionsSliderWidget = { ...optionsSliderBase };

            let mouseScroll = sliderWidget.data('mouse-scroll');
            let autoSpeed  = sliderWidget.data('auto-speed');
            let items  = sliderWidget.data('items');
            let autoplay  = sliderWidget.data('autoplay');
            let dots  = sliderWidget.data('dots');
            let nav  = sliderWidget.data('button');
            let touchDrag  = sliderWidget.data('touch-drag');
            let mouseDrag  = sliderWidget.data('mouse-drag');

            optionsSliderWidget.autoplayTimeout = autoSpeed;
            optionsSliderWidget.items = items;
            optionsSliderWidget.autoplay = autoplay;
            optionsSliderWidget.dots = dots;
            optionsSliderWidget.nav = nav;
            optionsSliderWidget.touchDrag = touchDrag;
            optionsSliderWidget.mouseDrag = mouseDrag;
            if (items == '1') optionsSliderWidget.responsive = {};

            if (Number(mouseScroll) === 1) {
                sliderWidget.on('mousewheel', '.owl-stage', function (e) {
                    if (e.originalEvent.deltaY > 0) {
                        sliderWidget.trigger('next.owl');
                    } else {
                        sliderWidget.trigger('prev.owl');
                    }
                    e.preventDefault();
                });
            }

            sliderWidget.on('initialized.owl.carousel', function () {
                setTimeout(function() {
                    initOwlAria(sliderWidget);
                }, 0);
            });

            sliderWidget.owlCarousel(optionsSliderWidget);
        });
    }


    if (document.querySelectorAll('.widget-slider-products').length) {
        $('.widget-slider-products').each(function () {
            let sliderWidget = $(this);
            let optionsSliderWidget = { ...optionsSliderBase };

            let items  = sliderWidget.data('items');
            let items_mob  = sliderWidget.data('items-mob');
            let dots  = sliderWidget.data('dots');
            let nav  = sliderWidget.data('nav');

            if (items_mob === undefined) items_mob = items;

            optionsSliderWidget.items = items_mob;
            optionsSliderWidget.dots = dots;
            optionsSliderWidget.nav = nav;
            optionsSliderWidget.responsive = {
                756: {
                    items: items
                }
            };
            sliderWidget.on('initialized.owl.carousel', function () {
                setTimeout(function() {
                    initOwlAria(sliderWidget);
                }, 0);
            });
            sliderWidget.owlCarousel(optionsSliderWidget);
        });
    }

    if (document.getElementById('slider-payment') !== null) {
        let sliderPayment = $('#slider-payment');
        let optionsSliderPayment = {...optionsSliderBase};
        optionsSliderPayment.autoplay = false;
        optionsSliderPayment.items = 5;
        optionsSliderPayment.loop = false;
        optionsSliderPayment.nav = false;

        sliderPayment.on('mousewheel', '.owl-stage', function (e) {
            if (e.originalEvent.deltaY > 0) {
                sliderPayment.trigger('next.owl');
            } else {
                sliderPayment.trigger('prev.owl');
            }
            e.preventDefault();
        });

        sliderPayment.owlCarousel(optionsSliderPayment);
    }
    if (document.getElementById('slider-main-specials') !== null) {
        let sliderPayment = $('#slider-main-specials');
        sliderPayment.owlCarousel(optionsSliderBase);
        //sliderPayment.on('mousewheel', '.owl-stage', function (e) {
            //if (e.originalEvent.deltaY > 0) {
               // sliderPayment.trigger('next.owl');
           // } else {
              //  sliderPayment.trigger('prev.owl');
           // }
           // e.preventDefault();
       // });
    }
    if (document.getElementById('slider-main-interesting') !== null) {
        let sliderPayment = $('#slider-main-interesting');
        sliderPayment.owlCarousel(optionsSliderBase);
    }
    if (document.getElementById('slider-delivery-company') !== null) {
        let sliderDeliveryCompany = $('#slider-delivery-company');
        let optionsSliderDelivery = {...optionsSliderBase};
        optionsSliderDelivery.autoplay = false;
        optionsSliderDelivery.items = 5;
        optionsSliderDelivery.loop = false;
        optionsSliderDelivery.nav = false;
        optionsSliderDelivery.dots = false;
        sliderDeliveryCompany.owlCarousel(optionsSliderDelivery);
        sliderDeliveryCompany.on('mousewheel', '.owl-stage', function (e) {
            if (e.originalEvent.deltaY > 0) {
                sliderDeliveryCompany.trigger('next.owl');
            } else {
                sliderDeliveryCompany.trigger('prev.owl');
            }
            e.preventDefault();
        });
    }
    if (document.getElementById('main-slider-reviews') !== null) {
        let sliderOldReviews = $('#main-slider-reviews');
        let optionsOldReviews = optionsSliderBase;
        optionsOldReviews.mouseDrag = true;
        optionsOldReviews.margin = 40;
        optionsOldReviews.dotsEach = 1;
        optionsOldReviews.responsive = { 0: {items: 1}, 576: {items: 2}, 768: {items: 3}, 991: {items: 4}, 1200: {items: 5}, 1400: {items: 6}};
        sliderOldReviews.owlCarousel(optionsOldReviews);
    }



    if (document.querySelectorAll('.slider-images-product') !== null) {

        let slidersImagesProduct = $('.slider-images-product');
        slidersImagesProduct.each(function (element) {
            let product_optionsSliderBase = optionsSliderBase;
            let sliderImagesProduct = $(this);
            let mouseScroll = sliderImagesProduct.data('mouse-scroll');
            let responsive = sliderImagesProduct.data('responsive');
            if (responsive === undefined || responsive.length !== 3) responsive = [2, 3, 4];
            product_optionsSliderBase.responsive = {0: {items: responsive[0]}, 576: {items: responsive[1]}, 991: {items: responsive[2]}};
            product_optionsSliderBase.loop = false;
            product_optionsSliderBase.margin = 0;
            if (mouseScroll !== 0) {
                sliderImagesProduct.on('mousewheel', '.owl-stage', function (e) {
                    if (e.originalEvent.deltaY > 0) {
                        sliderImagesProduct.trigger('next.owl');
                    } else {
                        sliderImagesProduct.trigger('prev.owl');
                    }
                    e.preventDefault();
                });
            }
            sliderImagesProduct.on('initialized.owl.carousel', function () {
                setTimeout(function() {
                    initOwlAria(sliderImagesProduct);
                }, 0);
            });
            sliderImagesProduct.owlCarousel(product_optionsSliderBase);
        });
    }
    if (document.getElementById('slider-old-catalog') !== null) {
        let sliderOldCatalog = $('#slider-old-catalog');
        let optionsOldCatalog = optionsSliderBase;
        optionsOldCatalog.mouseDrag = true;
        optionsOldCatalog.dots = true;
        optionsOldCatalog.nav = false;
        optionsOldCatalog.responsive = { 0: {items: 1}};
        sliderOldCatalog.owlCarousel(optionsOldCatalog);
    }

})();

