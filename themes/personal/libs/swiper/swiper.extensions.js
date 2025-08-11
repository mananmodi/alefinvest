(function($, Swiper){
  
'use strict';

  var Utils = {
    isObject: function isObject(o) {
      return typeof o === 'object' && o !== null && o.constructor && o.constructor === Object;
    },
    extend: function extend() {
      var args = [], len$1 = arguments.length;
      while ( len$1-- ) args[ len$1 ] = arguments[ len$1 ];

      var to = Object(args[0]);
      for (var i = 1; i < args.length; i += 1) {
        var nextSource = args[i];
        if (nextSource !== undefined && nextSource !== null) {
          var keysArray = Object.keys(Object(nextSource));
          for (var nextIndex = 0, len = keysArray.length; nextIndex < len; nextIndex += 1) {
            var nextKey = keysArray[nextIndex];
            var desc = Object.getOwnPropertyDescriptor(nextSource, nextKey);
            if (desc !== undefined && desc.enumerable) {
              if (Utils.isObject(to[nextKey]) && Utils.isObject(nextSource[nextKey])) {
                Utils.extend(to[nextKey], nextSource[nextKey]);
              } else if (!Utils.isObject(to[nextKey]) && Utils.isObject(nextSource[nextKey])) {
                to[nextKey] = {};
                Utils.extend(to[nextKey], nextSource[nextKey]);
              } else {
                to[nextKey] = nextSource[nextKey];
              }
            }
          }
        }
      }
      return to;
    },
    now: function() {
      return (Date.now ? Date.now() : new Date().getTime()) / 1000;
    }
  };

  var SmartPosition = {
    init: function init() {
      var swiper = this;
      var $wrapperEl = swiper.$wrapperEl;
      Utils.extend(swiper.params, {
        smartPosition: {
          slides: $wrapperEl.children(("." + (swiper.params.slideClass))).length,
          loop: swiper.params.loop,
          pagination: swiper.params.pagination,
          active: false,
          slidesPerView: swiper.params.slidesPerView,
        },
      });
    },
    validate: function validate() {
      var params = this.params;
      if (parseInt(params.slidesPerView, 10) >= params.smartPosition.slides) {
        return true;
      }
      return false;
    },
    update: function update() {
      var swiper = this;
      var params = swiper.params;
      var $wrapperEl = swiper.$wrapperEl;
      if (!params.smartPosition.enabled) return false;
      if (swiper.smartPosition.validate()) {
        if (!swiper.params.smartPosition.active) {
          params.smartPosition.active = true;
          params.smartPosition.centeredSlides = params.centeredSlides;
          params.loop = false;
          swiper.pagination.update();
          swiper.navigation.update();
          // swiper.$el.addClass(swiper.params.noSwipingClass);
          $wrapperEl.addClass(swiper.params.smartPosition.lockClass);
          if (params.centeredSlides) {
            params.centeredSlides = false;
            $wrapperEl.addClass(swiper.params.smartPosition.centerClass);
          }
          if (params.spaceBetween) {
            $wrapperEl.css('margin-right', -params.spaceBetween + 'px');
          }
        }
        return true;
      } else if (params.smartPosition.active) {
        params.smartPosition.active = false;
        params.loop = params.smartPosition.loop;
        swiper.pagination.update();
        swiper.navigation.update();
        // swiper.$el.removeClass(swiper.params.noSwipingClass);
        $wrapperEl.removeClass(swiper.params.smartPosition.lockClass);
        if (params.smartPosition.centeredSlides) {
          params.centeredSlides = true;
          $wrapperEl.removeClass(swiper.params.smartPosition.centerClass);
        }
        if (params.spaceBetween) {
          $wrapperEl.css('margin-right', '');
        }
      }
      return false;
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
      var snapTranslate = -snapGrid[activeIndex / params.slidesPerGroup];
      var diff = snapTranslate - swiper.getTranslate();

      // Fix For Negative Oversliding
      if (activeIndex < loopedSlides) {
        newIndex = (slides.length - (loopedSlides * 3)) + activeIndex;
        newIndex += loopedSlides;
        var slideChanged = swiper.slideTo(newIndex, 0, false, true);
        swiper.fixedIndex = newIndex;
        if (slideChanged && diff !== 0) {
          swiper.setTranslate((rtl ? -swiper.translate : swiper.translate) - diff);
        }
        if (swiper.touchEventsData.isTouched && swiper.touchEventsData.isMoved) {
          swiper.lock = true;
          swiper.needTouchFix = true;
          swiper.touchEventsData.startTranslate = swiper.translate;
        }
      } else if ((params.slidesPerView === 'auto' && activeIndex >= loopedSlides * 2) || (activeIndex >= slides.length - loopedSlides)) {
        // Fix For Positive Oversliding
        newIndex = -slides.length + activeIndex + loopedSlides;
        newIndex += loopedSlides;
        swiper.fixedIndex = newIndex;
        var slideChanged$1 = swiper.slideTo(newIndex, 0, false, true);
        if (slideChanged$1 && diff !== 0) {
          swiper.setTranslate((rtl ? -swiper.translate : swiper.translate) - diff);
        }
        if (swiper.touchEventsData.isTouched && swiper.touchEventsData.isMoved) {
          swiper.lock = true;
          swiper.needTouchFix = true;
          swiper.touchEventsData.startTranslate = swiper.translate;
        }
      }
      swiper.allowSlidePrev = allowSlidePrev;
      swiper.allowSlideNext = allowSlideNext;
    },
    slideNext: function(speed, runCallbacks, internal) {
      if ( speed === void 0 ) speed = this.params.speed;
      if ( runCallbacks === void 0 ) runCallbacks = true;

      var swiper = this;
      var params = swiper.params;
      if (params.loop) {
        if ((swiper.lastAnimation + 0.1) > Utils.now()) {
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
        if ((swiper.lastAnimation + 0.1) > Utils.now()) {
          return false;
        }
        swiper.lastAnimation = Utils.now();
        swiper.loopFix();
        swiper._clientLeft = swiper.$wrapperEl[0].clientLeft;
      }
      return swiper.slideTo(swiper.activeIndex - 1, speed, runCallbacks, internal);
    }
  }

  Swiper.prototype.modules.smartPosition = {
    name: 'smart-position',
    params: {
      smartPosition: {
        enabled: true,
        autoNavMarkup: true,
        lockClass: 'swiper-smart-position-lock',
        centerClass: 'swiper-smart-position-center',
        hiddenClass: 'swiper-smart-position-hidden',
      },
    },
    create: function create() {
      var swiper = this;
      var ref = swiper.params;
      var params = ref.smartPosition;
      Utils.extend(swiper, {
        smartPosition: {
          init: SmartPosition.init.bind(swiper),
          update: SmartPosition.update.bind(swiper),
          validate: SmartPosition.validate.bind(swiper),
        },
      });
 
      if (!params || !params.enabled) { return; }

      var paginationInit = swiper.pagination.init;
      swiper.pagination.init = function() {
        var params = swiper.params;
        if (params.smartPosition.autoNavMarkup) {
          if (params.pagination.el === 'auto') {
            var $parent = swiper.$el.parent();
            var $nav = $parent.find('.swiper-navigation');
            if ($nav.length === 0) {
              $nav = $('<div class="swiper-navigation"></div>');
              $parent.append($nav);
            }
            var $pagination = $('<div class="swiper-pagination"></div>');
            $nav.append($pagination);
            params.pagination.el = $pagination[0];
            swiper.originalParams.pagination.el = $pagination[0];
          }
        }
        paginationInit.call(this);
      }

      var navigationInit = swiper.navigation.init;
      swiper.navigation.init = function() {
        var params = swiper.params;
        if (params.smartPosition.autoNavMarkup) {
          var $parent = swiper.$el.parent();
          if (params.navigation.prevEl === 'auto' || params.navigation.nextEl === 'auto') {
            var $nav = $parent.find('.swiper-navigation');
            if ($nav.length === 0) {
              $nav = $('<div class="swiper-navigation"></div>');
              $parent.append($nav);
            }
            if (params.navigation.prevEl === 'auto') {
              var $arrow = $('<div class="swiper-button-prev swiper-button"></div>');
              $nav.prepend($arrow);
              params.navigation.prevEl = $arrow[0];
              swiper.originalParams.navigation.prevEl = $arrow[0];
            }
            if (params.navigation.nextEl === 'auto') {
              var $arrow = $('<div class="swiper-button-next swiper-button"></div>');
              $nav.append($arrow);
              params.navigation.nextEl = $arrow[0];
              swiper.originalParams.navigation.nextEl = $arrow[0];
            }
          }
        }
        navigationInit.call(this);
      }

      var navigationUpdate = swiper.navigation.update;
      swiper.navigation.update = function() {
        var params = swiper.params.navigation;
        var ref = swiper.navigation;
        var $nextEl = ref.$nextEl;
        var $prevEl = ref.$prevEl;
        if ($prevEl && $prevEl.length) {
          if (swiper.params.smartPosition.active) {
            $prevEl.addClass(swiper.params.smartPosition.hiddenClass);
          } else {
            $prevEl.removeClass(swiper.params.smartPosition.hiddenClass);
          }
        }
        if ($nextEl && $nextEl.length) {
          if (swiper.params.smartPosition.active) {
            $nextEl.addClass(swiper.params.smartPosition.hiddenClass);
          } else {
            $nextEl.removeClass(swiper.params.smartPosition.hiddenClass);
          }
        }
        navigationUpdate.call(this);
      }

      // swiper.__proto__.slideNext = function() {
      //   SmartPosition.slideNext.call(this);
      // }
      // swiper.__proto__.slidePrev = function() {
      //   SmartPosition.slidePrev.call(this);
      // }

      var loopCreate = swiper.__proto__.loopCreate;
      swiper.__proto__.loopCreate = function() {
        var swiper = this;
        var params = swiper.params.smartPosition;
        if (params && params.enabled) {
          if (swiper.smartPosition.update()) {
            return false;
          }
        }
        loopCreate.call(this);
      }

      // swiper.__proto__.loopFix = function() {
      //   SmartPosition.loopFix.call(this);
      // }
    },
    on: {
      beforeInit: function beforeInit() {
        var swiper = this;
        var params = swiper.params.smartPosition;
        if (!params || !params.enabled) { return; }
        // swiper.lastAnimation = Utils.now();
        swiper.smartPosition.init();
        swiper.smartPosition.update();
      },
      breakpoint: function breakpoint() {
        var swiper = this;
        var params = swiper.params.smartPosition;
        if (!params || !params.enabled) { return; }
        swiper.smartPosition.update();
      },
      paginationUpdate: function paginationUpdate(swiper, el) {
        var swiper = this;
        var params = swiper.params.smartPosition;
        if (!params || !params.enabled) { return; }
        var $el = $(el);
        if ($el.length && $el[0].childElementCount < 2 || swiper.params.smartPosition.active) {
          $el.addClass(swiper.params.smartPosition.hiddenClass);
          $el.html('');
        } else {
          $el.removeClass(swiper.params.smartPosition.hiddenClass);
        }
      }
    },
  }

  Swiper.prototype.setBreakpoint = function() {
    var swiper = this;
    var activeIndex = swiper.activeIndex;
    var initialized = swiper.initialized;
    var loopedSlides = swiper.loopedSlides; if ( loopedSlides === void 0 ) loopedSlides = 0;
    var params = swiper.params;
    var breakpoints = params.breakpoints;
    if (!breakpoints || (breakpoints && Object.keys(breakpoints).length === 0)) { return; }

    // Set breakpoint for window width and update parameters
    var breakpoint = swiper.getBreakpoint(breakpoints);

    if (breakpoint && swiper.currentBreakpoint !== breakpoint) {
      var breakpointOnlyParams = breakpoint in breakpoints ? breakpoints[breakpoint] : undefined;
      if (breakpointOnlyParams) {
        ['slidesPerView', 'spaceBetween', 'slidesPerGroup'].forEach(function (param) {
          var paramValue = breakpointOnlyParams[param];
          if (typeof paramValue === 'undefined') { return; }
          if (param === 'slidesPerView' && (paramValue === 'AUTO' || paramValue === 'auto')) {
            breakpointOnlyParams[param] = 'auto';
          } else if (param === 'slidesPerView') {
            breakpointOnlyParams[param] = parseFloat(paramValue);
          } else {
            breakpointOnlyParams[param] = parseInt(paramValue, 10);
          }
        });
      }

      var breakpointParams = breakpointOnlyParams || swiper.originalParams;
      var needsReLoop = (params.loop || params.smartPosition.loop) && (breakpointParams.slidesPerView !== params.slidesPerView);

      Utils.extend(swiper.params, breakpointParams);

      var smartLoopLock = false;
      if (params.smartPosition.enabled) {
        if (swiper.smartPosition.validate()) {
          smartLoopLock = true;
        }
      }

      Utils.extend(swiper, {
        allowTouchMove: smartLoopLock ? false : swiper.params.allowTouchMove,
        allowSlideNext: smartLoopLock ? false : swiper.params.allowSlideNext,
        allowSlidePrev: smartLoopLock ? false : swiper.params.allowSlidePrev,
      });

      swiper.currentBreakpoint = breakpoint;

      if (needsReLoop && initialized) {
        swiper.loopDestroy();
        swiper.loopCreate();
        swiper.updateSlides();
        swiper.slideTo((activeIndex - loopedSlides) + swiper.loopedSlides, 0, false);
        if (params.smartPosition.enabled) {
          swiper.loopFix();
        }
      }

      swiper.emit('breakpoint', breakpointParams);
    }
  }

})(Swiper.$, Swiper);
