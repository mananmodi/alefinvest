# Youtube Plugin

## Description

This plugin allows inserting Youtube videos using embed code or just the video
URL in CKEditor 4. The plugin is *NOT* compatible with CKEditor 5. If you need
to embed Youtube videos in a CKEditor 5 enabled text format, consider using the
media library functionality provided by Drupal core. CKEditor 4 is reaching its
end-of-life in 2023! It is recommended to make the switch before CKEditor 4
becomes unsupported.

Note that the limitation is with the JavaScript library itself. There is no
version compatible with CKEditor 5. See:

* https://ckeditor.com/cke4/addon/youtube
* https://github.com/fonini/ckeditor-youtube-plugin
* https://www.drupal.org/project/ckeditor

## Installation

Automatic Installation via Composer (recommended):

1.  If your site is managed via Composer (https://www.drupal.org/node/2718229),
use Composer to download the CKEditor Youtube module by running "composer require drupal/ckeditor_youtube".
2.  CKEditor Youtube module will automatically install the required Youtube plugin
in the libraries folder (/libraries).
3.  Enable CKEditor Youtube module in the Drupal admin.
4. Configure your WYSIWYG toolbar to include the button.

Manual Installation:

1. Download the plugin from https://ckeditor.com/cke4/addon/youtube
2. Extract the plugin in the root libraries folder (/libraries)
or change the plugin location to your desired location in the module settings.
3. Enable CKEditor Youtube module in the Drupal admin.
4. Configure your WYSIWYG toolbar to include the button.

Each filter format will now have a config tab for this plugin.

Reference folder structure:

```
.
|-- autoload.php
|-- core
|-- index.php
|-- libraries
|   `-- youtube
|       `-- youtube
|           |-- images
|           |-- lang
|           `-- plugin.js
|-- modules
|   `-- contrib
|       |-- ckeditor_youtube
|       |   |-- ckeditor_youtube.info.yml
|       |   |-- ...
|       |   `-- src
|-- profiles
|-- robots.txt
|-- sites
|-- themes
|-- update.php
`-- web.config
```

## Usage

Go to the Text formats and editors settings (/admin/config/content/formats) and
add the Youtube Button to any CKEditor-enabled text format you want.

If you are using the "Limit allowed HTML tags and correct faulty HTML" filter
make sure that Allowed HTML tags include:

```
<div> <iframe width height src frameborder allowfullscreen> <object> <param>
<a> <img>
```

If the filter is enabled, these tags should be added automatically the first
time you add the YouTube button to the toolbar.

To change the video once added, place the cursor right after the video and press
the DELETE key. The video is considered just a character and it will be deleted
as any other piece of text. Then you can embed the new video as usual.

## Dependencies

This module requires CKEditor 4 (drupal/ckeditor). It is *not* compatible
CKEditor 5 (drupal/ckeditor5).

## Uninstallation

1. Uninstall the module from 'Administer >> Modules'.
2. If your site is managed via Composer (https://www.drupal.org/node/2718229),
use Composer to remove the CKEditor Youtube module by running
"composer remove "drupal/ckeditor_youtube". The Youtube plugin will be
automatically removed from the libraries (/libraries) folder.

## MAINTAINERS

Mauricio Dinarte - https://www.drupal.org/u/dinarcon

## Credits

Initial development and maintenance by Agaric.
