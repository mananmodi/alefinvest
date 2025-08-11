CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------

Utility to simplify the translation of lines on the site.

Compared to drupal 7, in drupal 8 and 9 the translation of lines
is quite inconvenient: it is difficult to translate one line into
all languages ​​due to the need to constantly switch languages.

The module provides an alternative interface for translating lines:

* Translate a line into all languages ​​on one page without unnecessary clicks;
* Page of the translation line in all languages ​​(as in drupal 7);
* Possibility of translation via google translate service;
* Availability of the google translate service on the pages of translation
of field names, blocks, entities, views, etc .;
* Block page reloads if no data is saved;
* Exporting translations;

RECOMMENDED MODULES
-------------------

* No extra module is required.


INSTALLATION
------------

* Install as usual, see [Installing Modules](https://drupal.org/node/1897420)
  for further information.


CONFIGURATION
-------------

* No configuration is needed.


REQUIREMENTS
-------------

* google/cloud-translate: ^1.10

EXAMPLES
------------

## PHP
### Simple Text
```php
gtext('giz')->t('Home page')
gtext()->giz('Home page')
gtext()->giz->t('Home page')
\Drupal::service('gtext')->giz->t('Home page')
```

### With parameters:
```php
gtext()->giz('User @name', ['@name' => 'Admin'])
gtext('giz')->t('User @name', ['@name' => 'Admin'])
gtext()->giz->t('User @name', ['@name' => 'Admin'])
Drupal::service('gtext')->giz->t('User @name', ['@name' => 'Admin'])
```
### Plural:
```php
gtext()->giz->plural(1, '@count comment', '@count comments')
```

## Twig Filter

### Simple Text
```twig
{{ 'Home page'|gtext('giz') }}
```
### With parameters:
```twig
{{ 'User @name'|gtext('giz', {'@name': 'Admin'}) }}
```

### Global variable
```twig
{{ gtext.giz.t('Home page') }}
```

### Global variable with params
```twig
{{ gtext.giz.t('User @name', {'@name': 'Admin'}) }}
```

### Plural variants
```twig
gtext.giz.plural(4, '@count comment', '@count comments') }}
```
### Plural variants with params
```twig
{% set singular = '@count page from "%site"' %}
{% set plural = '@count pages from "%site"' %}
{{ gtext.giz.plural(4, singular, plural, {'%site': 'example site'}) }}
```
