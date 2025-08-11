(function ($) {
  $(document).ready(
    function () {
      window.reloadTranslations = function (lid) {
        console.log('Reloading ' + lid);
        $.post("/admin/config/texts/" + lid + "/reload", {}, {}, 'json')
        .done(
          function (data) {
            for (const [key, value] of Object.entries(data)) {
              var $field = $('.gtext-translatable-field[name="' + key + '"]');
              if ($field.length == 1 && !$field.hasClass('gtext-field-changed') ) {
                $field.val(value).data('original-text', value).keyup();
              }

              var allTranslated = true;
              $field.parents('tr').find('textarea.gtext-translatable-field').each(
                function () {
                  if ($(this).val() == '' ) {
                    allTranslated = false;
                  }
                }
              )
              if (!allTranslated ) {
                $field.parents('tr').find('.gtext-all-translate-google-button').show();
              } else {
                $field.parents('tr').find('.gtext-all-translate-google-button').hide();
              }
            }
          }
        )
        .fail(
          function () {
            alert(Drupal.t('Failure update translations of string. Please update window!'));
          }
        );
      }
      var submitButton = $('.gtext-translate-form [type="submit"]');

      $('.gtext-translate-form .fixed-save-button').attr('title', submitButton.val());
      $('.gtext-translate-form .fixed-save-button').click(
        function () {
          submitButton.mousedown().click();
        }
      )
      $('.gtext-translate-form table textarea').focus(
        function () {
          // Save field default text
          if (typeof $(this).data('original-text') != 'string' ) {
            $(this).data('original-text', $(this).val());
          }
        }
      );
      $('.gtext-translate-form table textarea').keyup(
        function () {
          // Update field status
          if ($(this).data('original-text') == $(this).val() ) {
            $(this).removeClass('gtext-field-changed');
            $('.gtext-edit-form-link[target="gtext_translator_' + $(this).data('lid') + '"]').show();
          } else {
            $('.gtext-edit-form-link[target="gtext_translator_' + $(this).data('lid') + '"]').hide();
            if (!$(this).hasClass('gtext-field-changed') ) {
              $(this).addClass('gtext-field-changed');
            }
          }
          // Update body status
          if ($(this).parents('table').find('.gtext-field-changed').length != 0 ) {
            if (!$('body').hasClass('gtext-content-changed') ) {
              $('body').addClass('gtext-content-changed')
              window.onbeforeunload = function (e) {
                var confirmationMessage = Drupal.t('It looks like you have been editing something. If you leave before saving, your changes will be lost.');

                (e || window.event).returnValue = confirmationMessage;
                return confirmationMessage;
              };
            }
          } else
          if ($('body').hasClass('gtext-content-changed') ) {
            $('body').removeClass('gtext-content-changed')
            window.onbeforeunload = null;
          }
          var allTranslated = true;
          $(this).parents('tr').find('textarea.gtext-translatable-field').each(
            function () {
              if ($(this).val() == '' ) {
                allTranslated = false;
              }
            }
          )
          if (!allTranslated ) {
            $(this).parents('tr').find('.gtext-all-translate-google-button').show();
          } else {
            $(this).parents('tr').find('.gtext-all-translate-google-button').hide();
          }
        }
      );
      $('.gtext-translate-form').get(0).onsubmit = function () {
        window.onbeforeunload = null;
        return true;
      };

      $('.gtext-edit-button[data-lid]').click(
        function () {
          var lid = $(this).data('lid');
          $('.replace_en_' + lid).show();
          $('.gtext-replaced-string-' + lid).hide();
          $(this).remove();
        }
      );
      $('.gtext-remove-button').click(
        function () {
          var result = confirm('Delete this string?');
          if (result ) {
            var id = $(this).attr('id');
            Drupal.ajax(
              {
                url:    $(this).data('url'),
                element:  $(this).parent().get(0),
                progress: {
                  type:  'throbber',
                },
                submit: {
                  lid: $(this).data('lid')
                }
              }
            )
            .execute();
          }
        }
      );
      $('.gtext-edit-form-link').click(
        function () {
          window.open($(this).data('url'), $(this).data('target'));
        }
      );
      $('.gtext-translatable-field[data-lang][data-text]').each(
        function () {
          $('<div class="gtext-translate-buttons"></div>').insertBefore(this);
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
      $('.gtext-translate-form tr td:first-child').each(
        function (index) {
          var $target = $(this);
          var $item   = $('<div class="gtext-all-translate-google-button" title="' + Drupal.t('Google Translate (all texts in this row)') + '"></div>');
          $item.insertAfter($target.find('.gtext-remove-button'));

          var allTranslated = true;
          $item.parents('tr').find('textarea.gtext-translatable-field').each(
            function () {
              if ($(this).val() == '' ) {
                allTranslated = false;
              }
            }
          )
          if (allTranslated ) {
            $item.hide();
          }
          $item.click(
            function () {
              $(this).parents('tr').find('td:last-child .gtext-all-translate-google-button').click();
            }
          )
        }
      );
      $('.gtext-translate-form tr td:last-child').each(
        function (index) {
          var $target = $(this);
          var $item   = $('<div class="gtext-all-translate-google-button" title="' + Drupal.t('Google Translate (all texts in this row)') + '"></div>');
          $target.append($item);

          var allTranslated = true;
          $item.parents('tr').find('textarea.gtext-translatable-field').each(
            function () {
              if ($(this).val() == '' ) {
                allTranslated = false;
              }
            }
          )
          if (allTranslated ) {
            $item.hide();
          }

          $item.click(
            function () {
              $item.parents('tr').find('.gtext-all-translate-google-button').hide();
              if ($item.parents('table').find('textarea.gtext-translatable-field').length == 0 ) {
                return;
              }

              var Finded = false;
              $item.parents('tr').find('textarea.gtext-translatable-field:not(.gtext-in-process)').each(function () {
                  if ($(this).val() != '' || Finded) {
                    return;
                  }
                  Finded = true;
                  var $textarea = $(this);
                  var $button_wrapper = $(this).parent().find('.gtext-translate-buttons') || $(this).parents('td').find('.gtext-translate-buttons');

                  if (typeof $textarea.data('original-text') != 'string' ) {
                    $textarea.data('original-text', $textarea.val());
                  }
                  $button_wrapper.addClass('gtext-in-process');
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
                      $button_wrapper.removeClass('gtext-in-process');
                      $textarea.trigger('keyup');
                      $item.click();
                    }
                  );
                }
              );
            }
          )
        }
      )
      $('table.translate-form-table thead tr th[data-col-lang]').each(
        function (index) {
          var $target = $(this);
          var $item   = $('<div class="gtext-all-translate-google-button" title="' + Drupal.t('Google Translate (all texts in this column)') + '"></div>');
          $target.append($item);

          var lang = $(this).data('col-lang');

          $item.click(
            function () {
              $('[data-col-lang="' + lang + '"] .gtext-all-translate-google-button').hide();
              if ($item.parents('table').find('textarea[name*="[' + lang + ']"]').length == 0 ) {
                return;
              }

              var Finded = false;
              $item.parents('table').find('textarea[name*="[' + lang + ']"]:not(.gtext-in-process)').each(function () {
                  if ($(this).val() != '' || Finded) {
                    return;
                  }
                  Finded = true;
                  var $textarea = $(this);
                  var $button_wrapper = $(this).parent().find('.gtext-translate-buttons') || $(this).parents('td').find('.gtext-translate-buttons');

                  if (typeof $textarea.data('original-text') != 'string' ) {
                    $textarea.data('original-text', $textarea.val());
                  }
                  $button_wrapper.addClass('gtext-in-process');
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
                      $button_wrapper.removeClass('gtext-in-process');
                      $textarea.trigger('keyup');
                      $item.click();
                    }
                  );
                }
              );
            }
          )
        }
      );

      $('.replaced_original_text.error[data-lid]').each(function () {
        $('a.gtext-edit-button[data-lid="' + $(this).data('lid') + '"]').remove();
        $('.replaced_original_text[data-lid="' + $(this).data('lid') + '"]').show();
      });

    }
  );
})(jQuery)
