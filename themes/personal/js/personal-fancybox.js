(function($, Drupal){
  var mergeOpts = function(opts1, opts2) {
    var rez = $.extend(true, {}, opts1, opts2);

    $.each(opts2, function(key, value) {
      if ($.isArray(value)) {
        rez[key] = value;
      }
    });

    return rez;
  };
  function _run(e, opts) {
    var items = [],
      index = 0,
      $target,
      value,
      instance;

    // Avoid opening multiple times
    if (e && e.isDefaultPrevented()) {
      return;
    }

    e.preventDefault();

    opts = opts || {};

    if (e && e.data) {
      opts = mergeOpts(e.data.options, opts);
    }

    $target = opts.$target || $(e.currentTarget).trigger("blur");
    instance = $.fancybox.getInstance();

    if (instance && instance.$trigger && instance.$trigger.is($target)) {
      return;
    }

    if (opts.selector) {
      items = $(opts.selector);
    } else {
      // Get all related items and find index for clicked one
      value = $target.attr("data-fancybox") || "";

      if (value) {
        items = e.data ? e.data.items : [];
        items = items.length ? items.filter('[data-fancybox="' + value + '"]') : $('[data-fancybox="' + value + '"]');
      } else {
        items = [$target];
      }
    }

    index = $(items).index($target);

    // Sometimes current item can not be found
    if (index < 0) {
      index = 0;
    }

    instance = $.fancybox.open(items, opts, index);

    // Save last active element
    instance.$trigger = $target;
  }

  $.fn.fancybox = function(options) {
    var selector;

    options = options || {};
    selector = options.selector || false;

    if (selector) {
      if (typeof selector === 'object') {
        selector.off("click.fb-start").on(
          "click.fb-start",
          {
            items: this,
            options: options
          },
          _run
        );
      } else {
        // Use body element instead of document so it executes first
        $("body")
          .off("click.fb-start", selector)
          .on("click.fb-start", selector, {options: options}, _run);
      }
    } else {
      this.off("click.fb-start").on(
        "click.fb-start",
        {
          items: this,
          options: options
        },
        _run
      );
    }

    return this;
  };


  $(document).ready(function(){

    var galleries = [
      '.block-certificates .field--name-field-block-images',
    ];

    $(galleries.join(',')).each(function(){
      var $this = $(this);
      $(this).fancybox({
        selector: $this.find('.js-fancybox'),
        infobar: false,
        thumbs: {
          autoStart: true,
          axis: 'x'
        },
        gutter: 0,
        zoomOpacity: true,
        buttons: [
          "close"
        ],
        loop: true,
        backFocus: false,
        animationEffect: 'zoom',
      });
    })

    $('.b-gallery__images').each(function(){
      $this = $(this);

      $this.fancybox({
        selector: $this.find('.swiper-slide:not(.swiper-slide-duplicate) .js-fancybox'),
        infobar: false,
        thumbs: {
          autoStart: true,
          axis: 'x'
        },
        gutter: 0,
        zoomOpacity: true,
        buttons: [
          "close"
        ],
        loop: true,
        animationEffect: 'zoom',
        backFocus: false,
      });

      $this.find('.swiper-slide-duplicate').on('click', '.js-fancybox', function(e){
        e.preventDefault();
        var index = $(this).closest('.swiper-slide').attr('data-swiper-slide-index');
        $(this).closest('.b-gallery__images').find('.swiper-slide:not(.swiper-slide-duplicate)[data-swiper-slide-index=' + index + '] .js-fancybox').trigger('click');
      })
    });
  });

})(jQuery, Drupal);
