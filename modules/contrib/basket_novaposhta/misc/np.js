(function ($) {
	Drupal.behaviors.NPAdmin = {
		attach: function (context, settings){
			$(once('mask', '[data-mask]')).each(function(){
				Inputmask({
					"mask": $(this).data('mask'),
					'clearIncomplete': true
				}).mask($(this));
			});
			if(drupalSettings.npPostUpdate){
				$('.novaposhta_en_view a').trigger('click');
				delete drupalSettings.npPostUpdate;
			}
			$(once('chosen_np_select', '.chosen_np_select')).each(function(){
				$(this).chosen({
					no_results_text: drupalSettings.no_results_text
				});
			});
			if ($('.chosen-container').length > 0) {
        $('.chosen-container').on('touchstart', function(e){
          e.stopPropagation(); e.preventDefault();
          // Trigger the mousedown event.
          $(this).trigger('mousedown');
        });
      }
      $(once('select2_np_select', '.select2_np_select')).each(function(){
        var obj = $(this);
        var min = obj.data('min');

        var options = {
          minimumResultsForSearch: 20,
          dropdownAutoWidth : true,
          width: '100%',
          placeholder: obj.find('option').eq(0).text(),
          language: {
            noResults: function () {
              return drupalSettings.no_results_text;
            },
            inputTooShort: function (args) {
              return drupalSettings.search_text;
            },
          },
          matcher: function(params, data) {
            // Do not display the item if there is no 'text' property
            if (typeof data.text === 'undefined')
            { return null; }

            // If there are no search terms, return all of the data
            if (params.term === '' || typeof params.term === 'undefined') {
              return data;
            } else {
              // `params.term` is the user's search term
              // `data.text` should be checked against
              var q = params.term.toLowerCase();
              var idx = data.text.toLowerCase().indexOf(q);
              if (idx > -1) {
                return $.extend({
                  'rank':(params.term.length / data.text.length)+ (data.text.length-params.term.length-idx)/(3*data.text.length)
                }, data, true);
              }
            }
            // Return `null` if the term should not be displayed
            return null;
          },
          sorter: function (data) {
            if(data && data.length>1 && data[0].rank){
              data.sort(function(a,b) {return (a.rank > b.rank) ? -1 : ((b.rank > a.rank) ? 1 : 0);} );
            }
            return data;
          }
        }

        if(min) {
          options.minimumInputLength = min;
        }

        obj.select2(options);
      });
		}
	}
})(jQuery);