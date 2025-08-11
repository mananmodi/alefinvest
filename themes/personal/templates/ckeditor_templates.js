// Override the default template set
CKEDITOR.addTemplates( 'default', {
	// The name of sub folder which hold the shortcut preview images of the
	// templates.  Determine base path of drupal installation if any
	// (ckeditor could possibly be loaded w/o drupalSettings).

	// The templates definitions.
	templates: [ 
		{
			title: 'Two columns',
			html: '<div class="x-ck-layout">' +
				'<div class="x-ck-layout__col"><p>Col 1 here</p></div>' + 
				'<div class="x-ck-layout__col"><p>Col 2 here</p></div>' +
			'</div>'
		},
		{
			title: 'Three columns',
			html: '<div class="x-ck-layout">' +
				'<div class="x-ck-layout__col"><p>Col 1 here</p></div>' + 
				'<div class="x-ck-layout__col"><p>Col 2 here</p></div>' +
				'<div class="x-ck-layout__col"><p>Col 3 here</p></div>' +
			'</div>'
		},
		{
			title: 'Top text',
			html: '<div class="x-ck-top-text">Enter top text here</div>'
		},
		{
			title: 'Text block',
			html: '<div class="x-ck-text-block"><p>Enter text heare</p></div>'
		}
	]
} );
