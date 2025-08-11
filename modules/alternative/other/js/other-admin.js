(function($, Drupal) {

  $(document).ready(function(){

    $('#toolbar-item-administration-tray.toolbar-tray-horizontal .toolbar-menu-administration > .toolbar-menu > .menu-item').on('mouseenter', '.menu-item', function(){
      var $this = $(this);
      var $menu = $this.closest('.toolbar-menu-administration > .toolbar-menu > .menu-item > .toolbar-menu');
      var $dropdown = $this.children('.toolbar-menu');
      if ($dropdown.length === 0 || $menu.length === 0) return false;

      var menuOffsetBottom = $(window).height() + $(window).scrollTop() - 25;
      var dropdownOffsetBottom = $this.offset().top + $dropdown.height();

      var menuOffsetTop = $menu.offset().top;
      var dropdownOffsetTop = $this.offset().top;

      if (dropdownOffsetBottom > menuOffsetBottom) {
        var diff = menuOffsetBottom - dropdownOffsetBottom - $this.height();
        if (-diff > (dropdownOffsetTop - menuOffsetTop )) {
          $dropdown.css({
            'margin-top': menuOffsetTop - $this.offset().top - $this.height()  + 'px',
          });
        } else {
          $dropdown.css('margin-top', diff + 'px');
        }
      }
    });    
  });

})(jQuery, Drupal);