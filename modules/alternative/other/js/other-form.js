(function($, Drupal) {

  Drupal.behaviors.other_form = {
    attach: function (context, settings) {
      once('otherSubmit', '.views-exposed-form.auto-submit').forEach((el) => {
        $(el).on('change', 'input, select', function(){
          other_auto_submit(this);
        });
      });

      once('other', '.header-filter').forEach((el) => {
        var $this = $(el);
        var $form = $('#views-exposed-form-company-catalog-page-1');
        $this.on('change', 'input[name="header_sort_by"]', function(){
          var $sortElement = $form.find('.form-item-sort-by select');
          $sortElement.val($(this).val());
          other_auto_submit($form);
        });

        $this.on('change', 'input[name="header_company_name"]', function(){
          var $el = $form.find('input[name="company_name"]');
          $el.val($(this).val());
          other_auto_submit($form);
        });

        $this.on('click', 'search-btn', function(){
          other_auto_submit($form);
        });

        $this.on('click', '.form-radios label', function(){
          if ($(this).prev().prop('checked')) {
            var $orderElement = $form.find('.form-item-sort-order select');
            var orderValue = $orderElement.val();
            if (orderValue === 'ASC') {
              $orderElement.val('DESC').trigger('change');
              $(this).addClass('desc');
            } else {
              $orderElement.val('ASC').trigger('change');
              $(this).removeClass('desc');
            }
            other_auto_submit($form);
          }
        });
      });

    }
  }

  window.other_auto_submit = function(obj){
    if ($(obj).closest('.mfp-container').length) {
      return;
    }
    $(obj).closest('form').find('.form-submit').trigger('click');
  }

})(jQuery, Drupal);


