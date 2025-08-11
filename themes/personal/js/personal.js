(function($, Drupal, drupalSettings){
  var animateFinish = true;
  function scrollToDiv(target, offset) {
    if (typeof(target) != 'object' || target.length === 0) return undefined

    animateFinish = false;
    $('html, body').stop().animate({
      scrollTop: target.offset().top - offset
    }, 500, function(){
      animateFinish = true;
    });
    return false;
  }

  var eventThrobberAppend = document.createEvent('Event');
  eventThrobberAppend.initEvent('throbberAppend', true, true);

  document.addEventListener('throbberAppend', function (e) {
    if ($('.ajax-progress-throbber').length) {
      var coordinates = $('.ajax-progress-throbber').get(0).getBoundingClientRect();
      var top = coordinates.top;
      var windowHeight = $(window).height();
      var bottom = coordinates.bottom - $(window).height();

      if (top < 0) {
        $('.ajax-progress-throbber .inner-throbber').css('top', -top + 'px');
      }
      if (bottom > 0) {
        $('.ajax-progress-throbber .inner-throbber').css('bottom', bottom + 'px');
      }
    }
    else {
      $('.ajax-progress-throbber .inner-throbber').css('top', 0);
    }

  }, false);

  Drupal.Ajax.prototype.setProgressIndicatorThrobber = function () {
    this.progress.element = $(Drupal.theme('ajaxProgressThrobber', this.progress.message));

      if ($(this.element).closest('form').length) {
        $(this.element).closest('form').append(this.progress.element);
      } else if ($(this.element).closest('.view').length) {
        if ($(this.element).closest('.node').length) {
          $(this.element).closest('.node').append(this.progress.element);
        } else {
          $(this.element).closest('.view').append(this.progress.element);
        }
      } else {
        $(this.element).after(this.progress.element);
      }


    document.dispatchEvent(eventThrobberAppend);
  };

  Drupal.theme.ajaxProgressThrobber = function (message) {
    var messageMarkup = typeof message === 'string' ? Drupal.theme('ajaxProgressMessage', message) : '';
    var throbber = '<div class="throbber">&nbsp;</div>';

    return '<div class="ajax-progress ajax-progress-throbber"><div class="inner-throbber">' + throbber + messageMarkup + '</div></div>';
  };

  Drupal.theme.ajaxProgressIndicatorFullscreen = function () {

    return '<div class="ajax-progress ajax-progress-fullscreen"><div class="indeterminate"></div></div>';
  };

  Drupal.Ajax.prototype.setProgressIndicatorFullscreen = function () {
    this.progress.element = $(Drupal.theme('ajaxProgressIndicatorFullscreen'));
    if ($(this.element).closest('.mfp-container').length) {
      $container = $('.mfp-container');
    }
    else {
      $container = $('.site-content');
    }

    if ($(window).scrollTop() - $container.offset().top > 0) {
      this.progress.element.addClass('is-fixed');
    }
    $container.prepend(this.progress.element);
    this.element.classList.add('ajax-loading');

  };

  var oldSuccess = Drupal.Ajax.prototype.success;
  Drupal.Ajax.prototype.success = function (response, status) {
    if (this.element) {
      this.element.classList.remove('ajax-loading');
    }
    oldSuccess.call(this, response, status);
  }

  function leading_zero(number) {
    return number < 10 ? '0' + number : number;
  }
  var requestInterval = function (fn, delay) {
    var requestAnimFrame = (function () {
      return window.requestAnimationFrame || function (callback, element) {
        window.setTimeout(callback, 1000 / 60);
      };
    })(),
    start = new Date().getTime(),
    handle = {};
    function loop() {
      handle.value = requestAnimFrame(loop);
      var current = new Date().getTime(),
      delta = current - start;
      if (delta >= delay) {
        fn.call();
        start = new Date().getTime();
      }
    }
    handle.value = requestAnimFrame(loop);
    return handle;
  };
  window.personalTimer = function(counters) {
    if (counters.length > 0) {
      function count() {
        var now = new Date().getTime() / 1000;
        counters.forEach(function(counter){
          var t = counter['date'] - now;
          var seconds = Math.floor((t % 60));
          var minutes = Math.floor((t / 60) % 60);
          var hours = Math.floor((t / (60 * 60)) % 24);
          var days = Math.floor(t / (60 * 60 * 24));
          if (t < 0) {
            counter['daysContainer'].text(0);
            counter['hoursContainer'].text(0);
            counter['minutesContainer'].text(0);
            counter['secondsContainer'].text(0);
          } else {
            counter['daysContainer'].text() != days ? counter['daysContainer'].text(days) : '';
            counter['hoursContainer'].text() != hours ? counter['hoursContainer'].text(leading_zero(hours)) : '';
            counter['minutesContainer'].text() != minutes ? counter['minutesContainer'].text(leading_zero(minutes)) : '';
            counter['secondsContainer'].text() != seconds ? counter['secondsContainer'].text(leading_zero(seconds)) : '';
          }
        });
      }
      count();
      requestInterval(function() {
        count();
      }, 1000);
    }
  }


  Drupal.behaviors.personal = {
    attach: function (context, settings) {

      once('personal_timer', '.node--type-promotion, .node--type-product').forEach((el) => {
        var counters = [];
        $(el).find('.b-counter').each(function(i){
          var $this = $(this);
          if ($this.data('time')) {
            counters[i] = [];
            counters[i]['date'] = $this.data('time');
            counters[i]['daysContainer'] = $this.find('.type-days .number');
            counters[i]['hoursContainer'] = $this.find('.type-hours .number');
            counters[i]['minutesContainer'] = $this.find('.type-minutes .number');
            counters[i]['secondsContainer'] = $this.find('.type-seconds .number');
          }
        });
        personalTimer(counters);
      });

      if (typeof Inputmask !== 'undefined') {
        Inputmask.extendDefaults({
          'removeMaskOnSubmit': false,
          'clearIncomplete': true,
          'showMaskOnHover': false,
        });
        var phones = [
          'input[name^=field_comment_phone]',
        ];
        once('phonemask', phones.join(', ')).forEach((el) => {
          $(el).inputmask({
            'mask': '+38(999)999-99-99',
          });
        });
      }

      if ($.fn.select2) {
        once('personal_select', 'select').forEach((el) => {
          var placeholder = $(el).find('option').eq(0);

          switch ($(el).attr('name')) {
            case 'novaposhta_fields[region]':
            case 'novaposhta_fields[point]':
            case 'novaposhta_fields[city]':
            case 'intime_fields[region]':
            case 'intime_fields[city]':
            case 'delivery_auto_fields[region]':
            case 'delivery_auto_fields[city]':
              var search = 20;
              break;
            default:
              var search = -1;
          }

          switch($(el).attr('name')) {
            case 'model[0]':
            case 'model[1]':
            case 'model[2]':

              var allowClear = true;
              break;
          }

          if (placeholder.val() === 'All') {
            placeholder = {
              id: 'All',
              text: placeholder.text()
            }
          } else if (placeholder.val() === '_none') {
            placeholder = {
              id: '_none',
              text: placeholder.text()
            }
          } else {
            placeholder = placeholder.text();
          }

          if (allowClear) {
            if (!isMobile) {

              $(el).select2({
                minimumResultsForSearch: search,
                dropdownAutoWidth : true,
                width: '100%',
                placeholder: placeholder,
                allowClear: true,
                language: {
                  noResults: function () {
                    return Drupal.t('No result found');
                  },
                },
              }).on("select2:unselecting", function(e) {
                $(el).data('state', 'unselected');
              }).on("select2:open", function(e) {
                if ($(el).data('state') === 'unselected') {
                  $(el).removeData('state');

                  var self = $(el);
                  self.select2('close');
                }
              });
            }
          } else {
            $(el).select2({
              minimumResultsForSearch: search,
              dropdownAutoWidth : true,
              width: '100%',
              placeholder: placeholder,
              language: {
                noResults: function () {
                  return Drupal.t('No result found');
                },
              },

            })
          }

        });
      }

      once('error_update', 'form, .form-type-managed-file').forEach((el) => {
        var $this = $(el);
        setTimeout(function(){
          $this.find('.form-item--error-message').each(function(i){
            var width = $(this).closest('.form-item').width();
            if ($(this).prev().length > 0) {
              var left = $(this).prev().position().left;
              $(this).css('left', left);
            } else {
              var left = $(this).next().position().left;
              $(this).css('left', left);
            }
            $(this).addClass('is-visible').css('transition-delay', i * 0.1 + 's');
          });
        }, 10);
        $this.find('input, textarea, select').on('focus', function(){
          $this.find('.form-item--error-message').removeClass('is-visible');
        });
        $this.find('.form-item--error-message').on('click', function(){
          $this.find('.form-item--error-message').removeClass('is-visible');
        });
      });

      once('file_status', '.form-type-managed-file').forEach((el) => {
        if ($(el).find('input[type="file"]').length && $(el).find('.image-widget').length) {
          $(el).addClass('upload-image-file');
        }
        if ($(el).find('input[type="file"]').length) {
          $(el).addClass('upload-image-file test');
        }
      });

      once('spoiler', '.block-drupal-seo-block, .field--name-field-product-description').forEach((el) => {
        var $content = $(el).find('.content, .field__item');

        if ($content.children().length > 3 && $content.outerHeight() > 300) {
          var $readMore = $('<div class="read-more">' + Drupal.t('More') + '</div>');
          $content.after($readMore);
          var totalHeight = 0;
          var $hide = null;
          $content.children().each(function(index){
            totalHeight += $(this).height();
            if (totalHeight > 150) {
              $hide = $content.children().eq(index);
              $hide.nextAll().hide();
              return false;
            }
          });
          if ($hide) {
            $readMore.on('click', function(e) {
              e.preventDefault();
              if ($(this).hasClass('is-open')) {
                $hide.nextAll().hide();
                $(this).removeClass('is-open')
                $(this).text(Drupal.t('More'));
              } else {
                $hide.nextAll().show();
                $(this).addClass('is-open');
                $(this).text(Drupal.t('Collapse'));
              }
            });
          }
        }
      });

      once('rating_check', '.comment-form').forEach((el) => {
        $(el).find('input[type="radio"]:checked').each(function(){
          var $parent = $(this).closest('.form-item');
          $parent.removeClass('is-active').siblings().removeClass('is-active');
          $parent.prevAll().addClass('is-active');
        });
      });

      once('ajax_comment_form', '.ajax-comments-form-reply').forEach((el) => {
        var $this = $(el);
        $this.wrap('<div class="magnific-popup mfp-with-anim"></div>');
        $this.parent().prepend('<div class="magnific-popup__title">' + Drupal.t('Add answer') + '</div>');
        $.magnificPopup.open({
          items: {
            src:  $this.parent(),
            type: 'inline'
          },
          removalDelay: 400,
          mainClass: 'mfp-zoom-in',
          autoFocusLast: false,
          callbacks: {
            afterClose: function() {
              $this.closest('.magnific-popup').remove();
            }
          }
        });

      });

      once('ajax_comment_form', '.ajax-comments-form-edit').forEach((el) => {
        $(el).prev().css('display', '');
        if (typeof drupalSettings.other !== 'undefined' && drupalSettings.other.form === 'comment_answer') {
          $.magnificPopup.close();
          drupalSettings.other.form = false;
        }
        var $this = $(el);
        $this.wrap('<div class="magnific-popup mfp-with-anim"></div>');
        $this.parent().prepend('<div class="magnific-popup__title">' + Drupal.t('Edit review') + '</div>');
        $.magnificPopup.open({
          items: {
            src:  $this.parent(),
            type: 'inline'
          },
          removalDelay: 400,
          mainClass: 'mfp-zoom-in',
          autoFocusLast: false,
          callbacks: {
            afterClose: function() {
              $this.remove();
            }
          }
        });
      });

      once('personal', '.block-views-block-video-reviews-block-1').forEach((el) => {
        var $view = $(el);
        var $container = $view.find('.video-wrapper');
        $(el).find('.entity-link').on('click', function(e){
          var $this = $(this);
          e.preventDefault();
          $container.html('');
          var src = $this.attr('href');
          var src = src.match(/(?:https?:\/{2})?(?:w{3}\.)?youtu(?:be)?\.(?:com|be)(?:\/watch\?v=|\/)([^\s&]+)/);
          if (src[1]) {
            $container.html('<iframe class="lazyload" data-src="https://www.youtube.com/embed/' + src[1] + '">');
          }
        });

        var $first = $view.find('.views-row').eq(0).find('a').trigger('click');
      });

      once('personal_compare', '.view-catalog.view-display-id-page_3').forEach((el) => {
        var $view = $(el);
        if ($view.find('.view-empty').length) {
          $view.find('.view-filters').hide();
          return;
        }
        var $table = $view.find('table');
        var $fixedTable = $('<table class="fixed-table"><thead></thead><tbody></tbody></table>');
        $table.find('th:first-of-type').each(function(){
          var $row = $('<tr></tr>');
          $fixedTable.find('thead').append($row.append($(this).clone().css('height', $(this).outerHeight() + 'px')));
        });
        $table.find('td:first-of-type').each(function(){
          var custom_class = $(this).parent().get(0).className;
          var $row = $('<tr class="' + custom_class + '"></tr>');
          $fixedTable.find('tbody').append($row.append($(this).clone().css('height', $(this).outerHeight() + 'px')));
        });
        $view.find('.content-wrapper').append($fixedTable);
      });


      once('vieved_products', '.block-viewed-products .view').forEach((el) => {
        if (!$(el).find('.view-content').length) {
          $(el).closest('.block-viewed-products').hide();
        }
      });

      once('hide_seo', '.block-drupal-seo-block').forEach((el) => {
        if (!$(el).find('.content').children().length) {
          $(el).hide();
        }
      });

      once('personal-comment-messages', '.comment-form-wrapper div[aria-label^="Status"]').forEach((el) => {
        let $this = $(el);
        let $form = $this.closest('.comment-form-wrapper').find('form');
        $form.css('display', 'none');
      });

    }
  };

  function offsetCenter(map, latlng, offsetx, offsety) {
    if ($(window).width() < 600) return;
    var scale = Math.pow(2, map.getZoom());

    var worldCoordinateCenter = map.getProjection().fromLatLngToPoint(latlng);
    var pixelOffset = new leaflet.maps.Point((offsetx/scale) || 0,(offsety/scale) ||0);

    var worldCoordinateNewCenter = new leaflet.maps.Point(
        worldCoordinateCenter.x - pixelOffset.x,
        worldCoordinateCenter.y + pixelOffset.y
    );

    var newCenter = map.getProjection().fromPointToLatLng(worldCoordinateNewCenter);

    map.setCenter(newCenter);

  }

  window.personalSwitchMap = function(obj, nomap) {
    var $this = $(obj);
    console.log($this);
    console.log(nomap);
    var $container = $this.closest('.view-content');

    $container.find('.shop-list__title').removeClass('is-active');
    $this.addClass('is-active');
    if (typeof Drupal.geolocation.maps[Drupal.geolocation.maps.length - 1] !== 'undefined' ){
      Drupal.geolocation.maps[Drupal.geolocation.maps.length - 1].leafletMap.setZoom(15);
      let map = Drupal.geolocation.maps[Drupal.geolocation.maps.length - 1].leafletMap;
      map.flyTo({lat: $(obj).data('lat'), lng: $(obj).data('lng')});

      let panBy = (map) => {
        map.panBy([-300, 0]);
      };
      panBy(map);
      // Drupal.geolocation.maps[Drupal.geolocation.maps.length - 1].leafletMap.flyTo({lat: $(obj).data('lat'), lng: $(obj).data('lng')});
      // Drupal.geolocation.maps[Drupal.geolocation.maps.length - 1].leafletMap.panBy([-300, 0]);
      // $this.trigger( "click" );
      // var latlng = new leaflet.maps.LatLng(
      //   $(obj).data('lat'),
      //   $(obj).data('lng')
      // );
      // offsetCenter(Drupal.geolocation.maps[Drupal.geolocation.maps.length - 1].leafletMap, latlng, 200);
    }
  }


  function checkTouchDevice() {
    return 'ontouchstart' in document.documentElement;
  }

  $(document).ready(function(){

    setTimeout(function(){
      $('body').removeClass('wait-load');
    }, 300);

    $('.menu--footer').on('click', '.mlid-16 > a', function(e){
      e.preventDefault();
      $('.block-header-contacts .track.item').trigger('click');
    });

    $('.basket-login').on('click', '.basket-login__link', function(e){
      e.preventDefault();
      $('.basket-login__link:not(is-active)').addClass('is-active');
      if ($(this).hasClass('type-exists')) {
        $(this).closest('.l_wrap').addClass('is-login');
      } else {
        $(this).closest('.l_wrap').removeClass('is-login')
      }
      $(this).removeClass('is-active');
    });

    var $scrollTop = $('<div class="scroll-top"></div>').appendTo('.site-footer');
    $scrollTop.on('click', function(){
      scrollToDiv($('.site-page'), 0);
    });

    function throttle(fn, wait) {
      var time = Date.now();
      return function() {
        if ((time + wait - Date.now()) < 0) {
          fn();
          time = Date.now();
        }
      }
    }
    var windowHeight = $(window).height();
    var footerHeight = $('.site-footer').outerHeight();

    $(window).on('resize', function(){
      windowHeight = $(window).height();
      footerHeight = $('.site-footer').outerHeight();
    });

    function scrollTopPosition() {
      if ($(window).scrollTop() > windowHeight / 2) {
        $scrollTop.addClass('is-visible');
        var bottomOffset = $(document).height() - windowHeight - $(window).scrollTop();
        if (bottomOffset + 45 < footerHeight) {
          $scrollTop.addClass('is-sticky')
        } else {
          $scrollTop.removeClass('is-sticky');
        }
      } else {
        $scrollTop.removeClass('is-visible');
      }
    }
    $(window).scroll(throttle(scrollTopPosition, 10));

    if ($(window).scrollTop() > windowHeight / 2) {
      $scrollTop.addClass('is-visible');
      var bottomOffset = $(document).height() - windowHeight - $(window).scrollTop();
      if (bottomOffset + 65 < footerHeight) {
        $scrollTop.addClass('is-sticky')
      } else {
        $scrollTop.removeClass('is-sticky');
      }
    }

    isTouchDevice = checkTouchDevice();

    $('.b-cMenu__btn').on('click', function(e){
      if (isTouchDevice && !$(this).hasClass('is-touched')) {
        e.preventDefault();
        $(this).addClass('is-touched');
      }
    })


    var menuDelay = null;
    var menuOverlay = $('<div class="menu-overlay"></div>');
    $('.b-cMenu__wrapper').on('mouseenter', function(){
      var $this = $(this);
      if (isTouchDevice) {
      } else {
        menuDelay = setTimeout(function(){
          $this.addClass('is-ready');
          $('<div class="menu-overlay"></div>').appendTo('body');
          setTimeout(function(){
            $('.menu-overlay').addClass('is-visible');
          }, 16)
        }, 400);
      }
    }).on('mouseleave', function(){
      clearTimeout(menuDelay);
      $(this).removeClass('is-ready');
      $('.menu-overlay').removeClass('is-visible');
      $('.b-cMenu__btn').removeClass('is-touched');
      setTimeout(function(){
        $('.menu-overlay').remove();
      }, 400);
    });



    if ($(window).width() > 767) {

      var hoverDelay = null;
      var hoverDelayTimeout = null;
      $('.b-cMenu__left > .b-cMenu__item.has-childs').on('mouseenter', function(){
        clearTimeout(hoverDelayTimeout);
        var $this = $(this);
        hoverDelay = $this;
        hoverDelayTimeout = setTimeout(function(){
          if (hoverDelay) {
            $('.b-cMenu__item').removeClass('is-open');
            hoverDelay = null;
          }
        }, 200);

      }).on('mouseleave', function(){
        if (hoverDelay) {
          hoverDelay = null
        } else {
          var $this = $(this);
          $this.addClass('is-open');
          setTimeout(function(){
            $this.removeClass('is-open');
          }, 500);
        }
      });

    }

    $('body').on('click', '.js-show-answer', function(e){
      var $this = $(this);
      if ($this.hasClass('is-open')) {
        $this.removeClass('is-open');
        $this.addClass('is-closed');
        $this.find('span').text(Drupal.t('Show answers'));
        $this.closest('.views-row').find('.indented').hide();
      } else if ($this.hasClass('is-closed')) {
        $this.addClass('is-open');
        $this.removeClass('is-closed');
        $this.find('span').text(Drupal.t('Hide answers'));
        $this.closest('.views-row').find('.indented').show();
      } else {
        $this.addClass('is-open');
        $this.find('span').text(Drupal.t('Hide answers'));
        e.preventDefault();
        var cid = $(this).attr('data-cid');
        var langPath = drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix;
        Drupal.ajax({
          url: langPath + 'api/other/comments/' + cid,
          element: this,
          progress: { type: 'fullscreen' }
        }).execute();
      }
    })



    $('body').on('change', 'input[name="field_comment_rating"]', function(){
      var $parent = $(this).closest('.form-item');
      $parent.removeClass('is-active').siblings().removeClass('is-active');
      $parent.prevAll().addClass('is-active');
    });

    $('body').on('click', '.ajax-loading', function(e){
      e.preventDefault();
    })

    var scroller = scrollama();
    scroller
    .setup({
      step: '.site-content',
      offset: 0,
    })
    .onStepEnter(function(element, index, direction) {
      if (element.direction === 'down') {
        $(element.element).find('.ajax-progress-fullscreen').addClass('is-fixed');
      }
    })
    .onStepExit(function(element) {
      if (element.direction === 'up') {
        $(element.element).find('.ajax-progress-fullscreen').removeClass('is-fixed');
      }
    });

    $('.phones').on('click', '.arrow', function(e){
      e.preventDefault();
      var $phones = $(this).closest('.phones');
      if($phones.hasClass('is-open')) {
        $phones.removeClass('is-open');
        $(document).off('click.phones');
      } else {
        $phones.addClass('is-open');
        var firstClick = true;
        $(document).on('click.phones', function(e) {
          if (!firstClick && $(e.target).closest($phones).length == 0) {
            $phones.removeClass('is-open');
            $(document).off('click.phones');
          }
        firstClick = false;
        });
      }
    });

    $('body').on('click', '.js-search-btn', function(e){
      e.preventDefault();
      var $this = $(this);
      var $search = $(this).parent().find('form');
      if($search.hasClass('is-open')) {
        $this.removeClass('is-open');
        $search.removeClass('is-open');
        $('#search-overlay').removeClass('is-open');
        setTimeout(function(){
          $('#search-overlay').remove();
        }, 400)
        $(document).off('click.search');
      } else {
        $this.addClass('is-open');
        $search.addClass('is-open');
        $('body').append('<div id="search-overlay"></div>');
        setTimeout(function(){
          $('#search-overlay').addClass('is-open');
        }, 16)
        var firstClick = true;
        $(document).on('click.search', function(e) {
          if (!firstClick && $(e.target).closest($search).length == 0) {
            $this.removeClass('is-open')
            $search.removeClass('is-open');
            $('#search-overlay').removeClass('is-open');
            setTimeout(function(){
              $('#search-overlay').remove();
            }, 400)
            $(document).off('click.search');
          }
        firstClick = false;
        });
      }
    });

    $('.js-personal-back').on('click', function(e){
      e.preventDefault();
      history.back();
    });

    $('a, img').on('dragstart', function(e){
      e.preventDefault();
    });


    /* ADAPTIVE */

    $('body').on('click', '.js-main-menu', function(e){
      e.preventDefault();
      var $this = $(this);
      var $menu = $('.mobile-menu');
      var $body = $('body');
      if($menu.hasClass('is-open')) {
        $this.removeClass('is-open');
        $menu.removeClass('is-open');
        $body.removeClass('lock');
        $(document).off('click.menu');
      } else {
        $this.addClass('is-open');
        $menu.addClass('is-open');
        $body.addClass('lock');
        var firstClick = true;
        $(document).on('click.menu', function(e) {
          if (!firstClick && ($(e.target).closest($menu).length == 0)) {
            $menu.removeClass('is-open');
            $this.removeClass('is-open');
            $body.removeClass('lock');
            $(document).off('click.menu');
          }
        firstClick = false;
        });
      }
    });

    if (isTouchDevice) {
      $('.b-cMenu').addClass('is-touch-device');
    }

    $('.b-cMenu__item').on('click', '.arrow', function(){
      if (isTouchDevice) {
        $('.b-cMenu__item').removeClass('is-open');
        $(this).closest('.b-cMenu__item').addClass('is-open');
      }
    }).on('click', '.b-cMenu__back', function(){
      $(this).closest('.b-cMenu__item').removeClass('is-open');
    });

    $('.b-cMenu__btn').on('click', function(e){
      if (isTouchDevice) {
        e.preventDefault();
        var $this = $(this);
        var $menu = $this.parent();
        if($this.hasClass('is-open')) {
          $this.removeClass('is-open');
          $menu.removeClass('is-open');
          $('.menu-overlay').removeClass('is-visible');
          $('.b-cMenu__btn').removeClass('is-touched');
          setTimeout(function(){
            $('.menu-overlay').remove();
          }, 400);
          $(document).off('click.catalog');
        } else {
          $this.addClass('is-open');
          $menu.addClass('is-open');
          $('<div class="menu-overlay"></div>').appendTo('body');
          setTimeout(function(){
            $('.menu-overlay').addClass('is-visible');
          }, 16)
          var firstClick = true;
          $(document).on('click.catalog', function(e) {
            if (!firstClick && ($(e.target).closest($menu).length == 0)) {
              $this.removeClass('is-open');
              $menu.removeClass('is-open');
              $('.menu-overlay').removeClass('is-visible');
              $('.b-cMenu__btn').removeClass('is-touched');
              setTimeout(function(){
                $('.menu-overlay').remove();
              }, 400);
              $(document).off('click.catalog');
            }
          firstClick = false;
          });
        }
      }
    });


    enquire.register('screen and (max-width: 1023px)', {

      match: function() {

        var $mobileMenuWrapper = $('<div class="mobile-menu"></div>');
        var $mobileMenu = $('<div class="mobile-menu__body"></div>');
        $mobileMenu.append($('.menu--main'));
        $mobileMenu.prepend($('#block-user-login'));


        var $languageBlock = $('#block-language-switcher');
        if (!$languageBlock.length) {
          var $languageBlock = $('[data-big-pipe-placeholder-id ^= "callback=Drupal%5Cblock%5CBlockViewBuilder%3A%3AlazyBuilder&args%5B0%5D=language_switcher"]');
        }
        $mobileMenu.prepend($languageBlock);
        $mobileMenuWrapper.prepend($mobileMenu);
        $('.site-header').append($mobileMenuWrapper);

      },
      unmatch: function() {
        $('#block-logo').after($('.menu--main'));
        $('.menu--main').after($('#block-user-login'));
        var $languageBlock = $('#block-language-switcher');
        if (!$languageBlock.length) {
          var $languageBlock = $('[data-big-pipe-placeholder-id ^= "callback=Drupal%5Cblock%5CBlockViewBuilder%3A%3AlazyBuilder&args%5B0%5D=language_switcher"]');
        }
        $('#block-search-block').after($languageBlock);
        $('.mobile-menu').remove();
      }
    });

    enquire.register('screen and (max-width: 767px)', {
      match: function() {
        if ($('body').find('.block-catalog-filter').length) {
          var $mobileButtonsWrapper = $('<div class="mobile-buttons-wrapper"></div>');
          $mobileButtonsWrapper.prepend('<div class="filter-btn js-categories-filter">' + Drupal.t('Categories') + '</div>');
          $mobileButtonsWrapper.prepend('<div class="filter-btn js-catalog-filter">' + Drupal.t('Filter') + '</div>');
          $('.region-content').prepend($mobileButtonsWrapper);

        }
      },
      unmatch: function() {
        // $('.mobile-contacts').remove();
        $('.mobile-buttons-wrapper').remove();
      }
    });

    $('body').on('click', '.js-mobile-apply-filter', function(){
      $('.block-catalog-filter').find('.form-submit').trigger('click');
      $.magnificPopup.close();
    });

    $('body').on('click', '.js-categories-filter', function(){
      var $target = $('.b-pMenu').eq(0).clone();
      $.magnificPopup.open({
        items: {
          src:  $target,
          type: 'inline'
        },
        removalDelay: 400,
        mainClass: 'mfp-zoom-in',
        autoFocusLast: false,
        fixedContentPos: true,
        fixedBgPos: isTouchDevice ? false : true,
        closeOnBgClick: false,
        callbacks: {
          beforeOpen: function() {
            $target.prepend('<div class="b-pMenu__title">' + Drupal.t('Categories') + '</div>')
            $target.addClass('mfp-with-anim');
          },
          afterClose: function() {
            $target.removeClass('mfp-with-anim mfp-hide');
            $('.b-pMenu__title').remove();
          }
        }
      });
    });

    $('body').on('click', '.js-catalog-filter', function(){
      var $target = $('.block-catalog-filter');
      if ($target.length) {
        $.magnificPopup.open({
          items: {
            src:  $target,
            type: 'inline'
          },
          removalDelay: 400,
          mainClass: 'mfp-zoom-in',
          autoFocusLast: false,
          fixedContentPos: true,
          fixedBgPos: isTouchDevice ? false : true,
          closeOnBgClick: false,
          callbacks: {
            beforeOpen: function() {
              $target.addClass('mfp-with-anim');
            },
            afterClose: function() {
              $target.removeClass('mfp-with-anim mfp-hide');
            },
            open: function() {
              $('.mfp-wrap').after('<div class="mobile-apply-filter js-mobile-apply-filter">' + Drupal.t('Apply filters') + '</div>');
            },
            close: function() {
              $('.js-mobile-apply-filter').remove();
            },
          }
        });
      }
    });
    // js-add-review

    $('body').on('click', '.js-add-review', function() {
      // var $target = $('.comment-form-wrapper');
      var $target = $('.b-product__comment-form');
      if ($target.length) {
        $.magnificPopup.open({
          items: {
            src:  $target,
            type: 'inline'
          },
          removalDelay: 400,
          mainClass: 'mfp-zoom-in',
          autoFocusLast: false,
          fixedContentPos: true,
          fixedBgPos: isTouchDevice ? false : true,
          closeOnBgClick: false,
          callbacks: {
            beforeOpen: function() {
              $target.addClass('mfp-with-anim  mfp--comment-product');
              $target.prepend('<div class="mfp--comment-product__title">' + Drupal.t('Send a review') + '</div>');
            },
            afterClose: function() {
              $('.mfp--comment-product__title').remove();
              $target.removeClass('mfp-with-anim mfp-hide');
            },
            open: function() {
              // $('.mfp-wrap').after('<div class="mobile-apply-filter js-mobile-apply-filter">' + Drupal.t('Apply filters') + '</div>');
            },
            close: function() {
              // $('.js-mobile-apply-filter').remove();
            },
          }
        });
      }
    });

    $('.block-exposed-form-catalog-page-4 form .form-submit').on('click', function() {
      $search = $(this).closest('form');
      $input = $search.find('input.form-text');
      $button = $('.js-search-btn');
      $button.removeClass('is-open');
      $search.removeClass('is-open');
      $('#search-overlay').removeClass('is-open');
      setTimeout(function(){
        $('#search-overlay').remove();
      }, 400)
      $(document).off('click.search');
    });


    $('body').on('click', '.b-pMenu__link-wrapper .arrow', function () {
      if ($(this).hasClass('is-active')) {
      $(this).removeClass('is-active');
      $(this).parent().next().hide(200);
      } else {
      $(this).addClass('is-active');
      $(this).parent().next().show(200);
      }
    });

    $('.addto_basket_button').on('click', function() {
      let $basketMessage = $('<div class="basket-message"><span class="basket-message__text">'+ Drupal.t('Product added to cart') +'</span></div>');
      const $body = $('body');
      $body.append($basketMessage);
    });
    if($('body').hasClass('page-sidebar')) {
      $('.form-item-price .ui-widget').draggable();
    }
  });

  $(window).on('load', function(){
    if ($(window).width() < 768) {
      $('.block-type-main-slider .paragraph--type--main-slider').each(function(){
        if ($(this).attr('data-mobile')) {
          $(this).attr('style', 'background-image: url(' + $(this).attr('data-mobile') + ') !important');
        }
      });
    }
  });


})(jQuery, Drupal, drupalSettings);

//123
