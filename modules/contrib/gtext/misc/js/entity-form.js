(function ($) {
  $.fn._setValue = $.fn.val;
  $.fn.val = function (n) {
    if (this.length != 1 || arguments.length != 1 ) {
      return $.fn._setValue.apply(this, arguments);
    }
    if (this.get(0).nodeName.toLowerCase() == 'input' ) {
      return $.fn._setValue.apply(this, arguments);
    }
    // CKeditor 4
    var id = $(this).attr('id');
    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances[id]) {
      CKEDITOR.instances[id].setData(n);
      return this;
    }
    // CKeditor 5
    var CK5ID = $(this).data('ckeditor5Id') + '';
    if ( CK5ID && Drupal && Drupal.CKEditor5Instances && Drupal.CKEditor5Instances.has(CK5ID) ){
      Drupal.CKEditor5Instances.get(CK5ID).setData(n);
    }
    return $.fn._setValue.apply(this, arguments);
  }
  Drupal.behaviors.gTextEntityTranslate = {
    attach: function () {
      $('form.gtext-translatable-form').each(
        function () {
          var $form = $(this);
          var googleURL = $form.data('gtext-google-url');

          var sourceLang = $form.data('gtext-source-language');
          var targetLang = $form.data('gtext-target-language');

          $form.find('input[type="text"]:not(.gtext-processed), textarea:not(.gtext-processed)').addClass('gtext-processed').each(
            function () {
              var $input = $(this);

              var text = $input.val();
              var formAction = $input.parents('form').attr('action');
              if (formAction.indexOf('/admin/structure/views') !== -1 || formAction.indexOf('/admin/structure/types/manage/') !== -1 || formAction.indexOf('/admin/structure/block/manage') !== -1 ) {
                if ($input.parents('.translation-set').find('[lang="' + sourceLang + '"] textarea').length != 0 ) { // Views with source textarea
                  text = $input.parents('.translation-set').find('[lang="' + sourceLang + '"] textarea').val();
                } else
                if ($input.parents('.translation-set').find('[lang="' + sourceLang + '"]').length != 0 ) { // Views
                  text = $input.parents('.translation-set').find('[lang="' + sourceLang + '"]').text();
                } else
                if ($input.val() == '' ) {
                  return;
                }
              } else
              if ($input.val() == '' ) {
                return;
              }

              var $buttonsWrapper = $('<div class="gtext-translate-buttons"></div>');
              if ($input.get(0).nodeName.toLowerCase() == 'input' ) {
                $buttonsWrapper.insertAfter($input);
              } else {
                $buttonsWrapper.insertBefore($input);
              }

              var id = $input.attr('id');
              if (!id ) {
                return;
              }

              $input.addClass('gtext-translatable-field');

              var $google = $('<div class="gtext-translate-google-button" title="Google Translate"></div>');
              $google.click(
                function () {
                  if ($input.hasClass('gtext-in-process') ) {
                    return false;
                  }
                  $input.addClass('gtext-in-process');
                  Drupal.ajax(
                    {
                      url:    googleURL,
                      element:  $buttonsWrapper.get(0),
                      progress: {
                        type:  'throbber',
                      },
                      submit: {
                        source: sourceLang,
                        target: id,
                        text:   text,
                        lang:   targetLang,
                      }
                    }
                  )
                  .execute();
                }
              );
              $buttonsWrapper.append($google);

              var $clear = $('<div class="gtext-translate-clear" title="Clear translate"></div>');
              $clear.click(
                function () {
                  if ($input.hasClass('gtext-in-process') ) {
                    return false;
                  }
                  $input.val(text).change();
                }
              );
              $buttonsWrapper.append($clear);
            }
          )
        }
      );
    }
  }
})(jQuery);
