(function ($, Drupal) {
	Drupal.behaviors.BasketOther = {
    attach: function(context, settings) {
      $('.ui_slider_init').each(function(){
        var obj = $(this);
        var min = $('input[name="'+obj.data('field')+'[min]"]').val();
        var max = $('input[name="'+obj.data('field')+'[max]"]').val();
        if(!min){
          min = obj.data('min');
          $('input[name="'+obj.data('field')+'[min]"]').val(min);
        }
        if(!max){
          max = obj.data('max');
          $('input[name="'+obj.data('field')+'[max]"]').val(max);
        }
        obj.slider({
          max: obj.data('max'),
          min: obj.data('min'),
          range: true,
          values: [min, max],
          step: 1,
          stop: function( event, ui ) {
            window.filter_auto_submit(obj);
          }
        }).bind('create slide slidestop update', function(event, ui) {
          $('input[name="'+obj.data('field')+'[min]"]').val(ui.values[0]);
          $('input[name="'+obj.data('field')+'[max]"]').val(ui.values[1]);
        });
      });
      once('tooltipster', '.tooltip').forEach((el) => {
        var obj = $(el);
        obj.tooltipster({
          content: $('.desc_'+obj.data('voc')).html(),
          contentAsHTML: true,
          animation: 'grow',
          delay: 200,
          theme: 'tooltipster-shadow',
          trigger: 'click',
          position: 'right',
          interactive: true,
          zIndex: 777,
          maxWidth: 400
        });
      });
    }
  };
  window.slider_set_val_onkeyup = function(obj, type){
    $('.ui_slider_init_'+type).slider( "option", "values", [$('input[name="'+type+'[min]"]').val(), $('input[name="'+type+'[max]"]').val()]);
    window.filter_auto_submit(obj);
  }
  window.filter_auto_submit = function(obj){
    if ($(obj).closest('.mfp-container').length) {
      return;
    }
    $(obj).parents('form:first').find('.form-submit').trigger('click');
  }
  window.filter_change_sort = function(obj){
    $('input[name="sort"]').val($(obj).val()).parents('form:first').find('.form-submit').trigger('click');
  }
  window.CheckboxesShow = function(obj, key){
    var text = $(obj).data('text');
    if(!$(obj).data('show')){
      $('[data-show="'+key+'"]').show();
      $(obj).data('show', true).data('text', $(obj).text()).text(text);
    } else {
      $('[data-show="'+key+'"]').hide();
      $(obj).data('show', false).data('text', $(obj).text()).text(text);
    }
  }
})(jQuery, Drupal);
