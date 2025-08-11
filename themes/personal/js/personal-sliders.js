(function($, Swiper, Drupal){

  var SwiperMarkup = function($container) {
    $container = $($container);
    this.container = $container;

    var slider = $('<div class="swiper-container"></div>');
    var wrapper = $('<div class="swiper-wrapper"></div>');
    this.slider = slider;

    var slides = $container.children();
    slides = slides.wrap('<div class="swiper-slide"></div>').parent();

    wrapper.append(slides.detach());
    slider.append(wrapper.detach());
    $container.html(slider);
  }

  SwiperMarkup.prototype.destroy = function() {
    if (!this.container) { return; }
    var elements = this.container.find('.swiper-slide').children();
    this.container.html(elements);
  }

  Drupal.behaviors.personal_sliders = {

    attach: function (context, settings) {

      once('personal_slider', '.block-type-main-slider').forEach((el) => {
        var $this = $(el);
        var $container = $this.find('.swiper-container');
        var interleaveOffset = 0.5;
        var callbacks = {}
        if ($(window).width() > 959) {
          callbacks = {
            progress: function() {
              var swiper = this;
              for (var i = 0; i < swiper.slides.length; i++) {
                var slideProgress = swiper.slides[i].progress;
                var innerOffset = swiper.width * interleaveOffset;
                var innerTranslate = slideProgress * innerOffset;
                swiper.slides[i].querySelector(".slide-inner").style.transform =
                  "translate3d(" + innerTranslate + "px, 0, 0)";
              }
            },
            touchStart: function() {
              var swiper = this;
              for (var i = 0; i < swiper.slides.length; i++) {
                swiper.slides[i].style.transition = "";
              }
            },
            setTransition: function(speed) {
              var swiper = this;
              for (var i = 0; i < swiper.slides.length; i++) {
                swiper.slides[i].style.transition = speed + "ms";
                swiper.slides[i].querySelector(".slide-inner").style.transition =
                  speed + "ms";
              }
            },
          }
        }


        var slider = new Swiper($container, {
          speed: 500,
          slidesPerView: 1,
          // loop: true,
          watchSlidesVisibility: true,
          autoplay: {
            delay: 6000,
            disableOnInteraction: false,
          },
          breakpoints: {
            959: {
              speed: 400
            }
          },
          on: callbacks
        });

        $this.find('.lazyloading').each(function(){
          lazySizes.loader.unveil(this);
        });
      });

      once('personal_slider', '.b-gallery').forEach((el) => {
        var $this = $(el);
        var smartLock = false;
        var thumbsSlider = new Swiper($this.find('.b-gallery__thumbs .swiper-container'), {
          speed: 500,
          slidesPerView: 4,
          loop: true,
          loopedSlides: 6,
          slideToClickedSlide: true,
          spaceBetween: 10,
          autoLoop: {
            navigationDelay: 0.2
          },
          navigation: {
            nextEl: 'auto',
            prevEl: 'auto'
          },
          on: {
            init: function() {
              if (this.$wrapperEl.hasClass('swiper-smart-position-lock')) {
                smartLock = true;
              }
            },
            transitionStart: function(){
              if (imageSlider) {
                imageSlider.slideTo(this.activeIndex);
              }
            },
            tap: function() {
              if (this.$wrapperEl.hasClass('swiper-smart-position-lock')) {
                imageSlider.slideTo(this.clickedIndex);
              }
            }
          }
        });

        var imageSlider = new Swiper($this.find('.b-gallery__images .swiper-container'), {
          speed: 500,
          effect: 'fade',
          loop: true,
          loopedSlides: 6,
          followFinger: false,
          watchSlidesVisibility: true,
          fadeEffect: {
            crossFade: true
          },
        });

      });

    }

  }

})(jQuery, Swiper, Drupal);
