(function ($) {
  $(document).ready(
    function () {
      if (!opener ) {
        $('#submit_form_close').hide();
      }
      var submitButton = $('.gtext-translate-form [type="submit"]');

      // Save original values
      $('#form__translate_string_form textarea').focus(
        function () {
          // Save field default text
          if (typeof $(this).data('original-text') != 'string' ) {
            $(this).data('original-text', $(this).val());
          }
        }
      );
      $('#form__translate_string_form textarea').keyup(
        function () {
          // Update field status
          if ($(this).data('original-text') == $(this).val() ) {
            $(this).removeClass('gtext-field-changed');
          } else {
            if (!$(this).hasClass('gtext-field-changed') ) {
              $(this).addClass('gtext-field-changed');
            }
          }
          // Update body status
          if ($(this).parents('form').find('.gtext-field-changed').length != 0 ) {
            if (!$('body').hasClass('gtext-content-changed') ) {
              $('body').addClass('gtext-content-changed')
              window.onbeforeunload = function (e) {
                var confirmationMessage = 'It looks like you have been editing something. '
                    + 'If you leave before saving, your changes will be lost.';

                (e || window.event).returnValue = confirmationMessage;
                return confirmationMessage;
              };
            }
          } else
          if ($('body').hasClass('gtext-content-changed') ) {
            $('body').removeClass('gtext-content-changed')
            window.onbeforeunload = null;
          }
        }
      );
      $('#form__translate_string_form').get(0).onsubmit = function () {
          window.onbeforeunload = null;
          return true;
      };

      $('.gtext-translatable-field[data-lang][data-text]').each(
        function () {
          $('<div class="gtext-translate-buttons"></div>').insertAfter(this);
        }
      )
      $('.gtext-translatable-field[data-lang][data-text]').each(function () {
          var id = $(this).attr('id');
          var $item = $('<div class="gtext-translate-google-button" title="Google Translate" data-for="' + id + '"></div>');
          var $target = $(this);
          var $wrapper = $(this).parent().find('.gtext-translate-buttons');
          $item.click(
            function () {
              if (typeof $target.data('original-text') != 'string' ) {
                  $target.data('original-text', $target.val());
              }
              if ($wrapper.hasClass('gtext-in-process') ) {
                return false;
              }
              $wrapper.addClass('gtext-in-process');
              Drupal.ajax(
                {
                  url:    $target.data('url'),
                  element:  $(this).get(0),
                  progress: {
                    type:  'throbber',
                    message: '',
                  },
                  submit: {
                    target: id,
                    text: $target.data('text'),
                    lang: $target.data('lang'),
                  }
                }
              )
              .execute()
              .done(
                function () {
                    $wrapper.removeClass('gtext-in-process');
                    $target.trigger('keyup');
                }
              );
            }
          );
          $(this).parent().find('.gtext-translate-buttons').append($item);
        }
      );
      $('.page-title').each(
        function () {
          var $buttonWrapper = $('<div class="gtext-translate-buttons"></div>');
          $(this).append($buttonWrapper);

          var $button = $('<div class="gtext-all-translate-google-button" title="Google Translate" data-plural-count="' + $(this).data('plural-count') + '" data-lang="' + $(this).data('lang') + '"></div>');
          $buttonWrapper.append($button);

          $button.click(
            function (e) {
              e.preventDefault();
              if ($('#form__translate_string_form .gtext-translatable-field').length != 0 ) {
                  $button.hide();
              } else {
                $button.show();
                return;
              }

              var Finded = false;
              $('#form__translate_string_form .gtext-translatable-field:not(.gtext-in-process)').each(function () {
                  if ($(this).val() != '' || Finded) {
                    return;
                  }

                  Finded = true;
                  var $textarea = $(this);
                  var $button_wrapper = $(this).parents('form').find('.gtext-translate-google-button[data-for="' + $textarea.attr('id') + '"]').parent();

                  if (typeof $textarea.data('original-text') != 'string' ) {
                    $textarea.data('original-text', $textarea.val());
                  }
                  $button_wrapper.addClass('gtext-in-process');
                  $textarea.addClass('gtext-in-process');
                  Drupal.ajax(
                    {
                      url:    $textarea.data('url'),
                      element:  $button_wrapper.find('.gtext-translate-google-button').get(0),
                      progress: {
                        type:  'throbber',
                        message: '',
                      },
                      submit: {
                        target: $textarea.attr('id'),
                        text:   $textarea.data('text'),
                        lang:   $textarea.data('lang'),
                      }
                    }
                  )
                  .execute()
                  .done(
                    function () {
                      $textarea.removeClass('gtext-in-process');
                      $button_wrapper.removeClass('gtext-in-process');
                      $textarea.trigger('keyup');
                      $button.click();
                    }
                  );
                }
              );
              return false;
            }
          );
        }
      );
      $('details[data-lang][data-plural-count]').each(
        function () {
          $(this).find('summary span').remove();

          var lang = $(this).data('lang');

          var $buttonWrapper = $('<div class="gtext-translate-buttons"></div>');
          $(this).find('summary').append($buttonWrapper);

          var $button = $('<div class="gtext-all-translate-google-button" title="Google Translate" data-lang="' + lang + '"></div>');
          $buttonWrapper.append($button);

          $button.click(
            function (e) {
              e.preventDefault();
              if ($button.parents('form').find('textarea[name*="[' + lang + ']"]').length != 0 ) {
                  $button.hide();
              } else {
                $button.show();
                return;
              }

              var Finded = false;
              $button.parents('form').find('textarea[name*="[' + lang + ']"]:not(.gtext-in-process)').each(function () {
                  if ($(this).val() != '' || Finded) {
                      return;
                  }
                  Finded = true;
                  var $textarea = $(this);
                  var $button_wrapper = $(this).parents('form').find('.gtext-translate-google-button[data-for="' + $textarea.attr('id') + '"]').parent();

                  if (typeof $textarea.data('original-text') != 'string' ) {
                      $textarea.data('original-text', $textarea.val());
                  }
                  $button_wrapper.addClass('gtext-in-process');
                  $textarea.addClass('gtext-in-process');
                  Drupal.ajax(
                    {
                      url:      $textarea.data('url'),
                      element:  $button_wrapper.find('.gtext-translate-google-button').get(0),
                      progress: {
                        type:    'throbber',
                        message: '',
                      },
                      submit: {
                        target: $textarea.attr('id'),
                        text:   $textarea.data('text'),
                        lang:   $textarea.data('lang'),
                      }
                    }
                  )
                  .execute()
                  .done(
                    function () {
                        $textarea.removeClass('gtext-in-process');
                        $button_wrapper.removeClass('gtext-in-process');
                        $textarea.trigger('keyup');
                        $button.click();
                    }
                  );
                }
              );
              return false;
            }
          );
        }
      )
    }
  );
})(jQuery)
