(function($, Swiper){

  'use strict';

  var Utils = {
    now: function() {
      return (Date.now ? Date.now() : new Date().getTime()) / 1000;
    }
  }

  var AutoLoop = {
    slideNext: function(speed, runCallbacks, internal) {
      if ( speed === void 0 ) speed = this.params.speed;
      if ( runCallbacks === void 0 ) runCallbacks = true;

      var swiper = this;
      var params = swiper.params;
      if (params.loop) {
        if ((swiper.lastAnimation + params.autoLoop.navigationDelay) > Utils.now()) {
          return false;
        }
        swiper.lastAnimation = Utils.now();
        swiper.loopFix();
        swiper._clientLeft = swiper.$wrapperEl[0].clientLeft;
        return swiper.slideTo(swiper.activeIndex + params.slidesPerGroup, speed, runCallbacks, internal);
      }
      return swiper.slideTo(swiper.activeIndex + params.slidesPerGroup, speed, runCallbacks, internal);
    },
    slidePrev: function (speed, runCallbacks, internal) {
      if ( speed === void 0 ) speed = this.params.speed;
      if ( runCallbacks === void 0 ) runCallbacks = true;

      var swiper = this;
      var params = swiper.params;

      if (params.loop) {
        if ((swiper.lastAnimation + params.autoLoop.navigationDelay) > Utils.now()) {
          return false;
        }
        swiper.lastAnimation = Utils.now();
        swiper.loopFix();
        swiper._clientLeft = swiper.$wrapperEl[0].clientLeft;
      }
      return swiper.slideTo(swiper.activeIndex - params.slidesPerGroup, speed, runCallbacks, internal);
    },
    loopFix: function() {
      var swiper = this;
      var params = swiper.params;
      var activeIndex = swiper.activeIndex;
      var slides = swiper.slides;
      var loopedSlides = swiper.loopedSlides;
      var allowSlidePrev = swiper.allowSlidePrev;
      var allowSlideNext = swiper.allowSlideNext;
      var snapGrid = swiper.snapGrid;
      var rtl = swiper.rtlTranslate;
      var newIndex;
      swiper.allowSlidePrev = true;
      swiper.allowSlideNext = true;
      swiper.lock = true;
  
      var snapTranslate = -snapGrid[activeIndex / params.slidesPerGroup];
      var diff = snapTranslate - swiper.getTranslate();

      // Fix For Negative Oversliding
      if (activeIndex < loopedSlides) {
        newIndex = (slides.length - (loopedSlides * 3)) + activeIndex;
        newIndex += loopedSlides;

        var slideChanged = swiper.slideTo(newIndex, 0, false, true);
        if (slideChanged && diff !== 0) {
          swiper.setTranslate((rtl ? -swiper.translate : swiper.translate) - diff);
        }
        if (swiper.touchEventsData.isTouched && swiper.touchEventsData.isMoved) {
          swiper.needTouchFix = true;
          swiper.touchEventsData.startTranslate = swiper.translate;
        }
      } else if ((params.slidesPerView === 'auto' && activeIndex >= loopedSlides * 2) || (activeIndex >= slides.length - loopedSlides)) {
        // Fix For Positive Oversliding
        newIndex = -slides.length + activeIndex + loopedSlides;
        newIndex += loopedSlides;
        var slideChanged$1 = swiper.slideTo(newIndex, 0, false, true);
        // console.log.classList.add('swiper-slide-active'));
        if (slideChanged$1 && diff !== 0) {
          swiper.setTranslate((rtl ? -swiper.translate : swiper.translate) - diff);
        }
        if (swiper.touchEventsData.isTouched && swiper.touchEventsData.isMoved) {
          swiper.needTouchFix = true;
          swiper.touchEventsData.startTranslate = swiper.translate;
        }
      }
      swiper.allowSlidePrev = allowSlidePrev;
      swiper.allowSlideNext = allowSlideNext;
      swiper.lock = false;
    },
    touchFix: function(e) {
      var swiper = this;
      var touches = swiper.touches;
      var startX = e.type === 'touchmove' ? e.targetTouches[0].pageX : e.pageX;
      var startY = e.type === 'touchmove' ? e.targetTouches[0].pageY : e.pageY;
      touches.startX = startX;
      touches.startY = startY;
      //prevent slider stuck on touchend event because of 0 diff
      touches.startX -= 0.001;
    }
  }

  Swiper.prototype.modules.autoLoop = {
    name: 'auto-loop',
    params: {
      autoLoop: {
        enabled: true,
        navigationDelay: 0.2
      },
    },
    create: function create() {
      var swiper = this;
      var params = swiper.params.autoLoop;

      if (!params || !params.enabled) { return; }

      swiper.lastAnimation = 0;
      swiper.params.watchSlidesProgress = true;
 
      swiper.slideNext = function() {
        AutoLoop.slideNext.call(this);
      }

      swiper.slidePrev = function() {
        AutoLoop.slidePrev.call(this);
      }

      swiper.loopFix = function() {
        AutoLoop.loopFix.call(this);
      }

      swiper.updateProgress = function(translate) {
        if (this.needTouchFix) { return; }
        swiper.__proto__.updateProgress.call(this, translate);
      }

      swiper.setTranslate = function(translate, byController) {
        if (this.needTouchFix) { return; }
        swiper.__proto__.setTranslate.call(this, translate, byController);
      }
    },
    on: {
      slideChange: function() {
        if (this.activeIndex === 0) { return; }
        var swiper = this;
        var params = swiper.params;
        if (!params.autoLoop || !params.autoLoop.enabled) { return; }
        var data = swiper.touchEventsData;
        if (data.isTouched && data.isMoved && swiper.realIndex !== swiper.previousRealIndex) {
          var $slide = swiper.$(swiper.slides[swiper.activeIndex]);
          var slidesRightOffset = params.centeredSlides ? Math.floor((params.slidesPerView + params.slidesPerGroup) / 2) + 1: params.slidesPerView + 1;
          var slidesLeftOffset = params.centeredSlides ? Math.floor((params.slidesPerView + params.slidesPerGroup) / 2) + 1 : params.slidesPerGroup + 1;
          if ($slide.index() <= slidesLeftOffset || $slide.index() >= swiper.slides.length - slidesRightOffset) {
            swiper.previousRealIndex = swiper.realIndex;
            swiper.loopFix();
          }
        }
      },
      touchMove: function(e) {
        var swiper = this;
        var params = swiper.params;
        if (!params.autoLoop || !params.autoLoop.enabled) { return; }
        if (swiper.needTouchFix) {
          var data = swiper.touchEventsData;
          var isMoved = data.isMoved;
          var isTouched = data.isTouched;
          data.isMoved = false;
          data.isTouched = false;
          AutoLoop.touchFix.call(this, e);
          swiper.needTouchFix = false;
          data.isMoved = isMoved;
          data.isTouched = isTouched;
        }
      },
    }
  }

})(Swiper.$, Swiper);
