# CKEditor Non-breaking space Plugin

Minimal module to insert a non-breaking space (`&nbsp;`)
into the content by pressing Ctrl+Space or using the provided button.

## Uses

During content creation the author may add a non-breaking space (`&nbsp;`)
to prevent an automatic line break.
To avoid that a company’s 2-word name is split onto 2 separate lines.

As the non-breaking space is an invisible character,
they are highlighted in blue on the CKEditor.

## Installation

Install the module then follow the instructions
for installing the CKEditor plugins below.

## Configuration

Go to the [Text formats and editors](/admin/config/content/formats)
configuration page:, and for each text format/editor combo
where you want to use NBSP, do the following:

* Drag and drop the 'NBSP' button into the Active toolbar.
* Enable filter "Cleanup NBSP markup".
* if the "Limit allowed HTML tags and correct faulty HTML" filter is disabled
you dont have anything to do with this text format.
Otherwise, add `<nbsp>` in the "allowed HTML tags" field.

## Which version should I use?

NBSP is now available for both Drupal 8, Drupal 9 & Drupal 10!

- if you are running Drupal `8.x`, use NBSP `8.x-2.0-alpha1`.
- if you are running Drupal `9.x`, use NBSP `2.x`.
- if you are running Drupal `10.x`, use NBSP `3.x`.

| Drupal Core | CKeditor |     NBSP     |
|:-----------:|:--------:|:------------:|
|    8.7.x    |   4.x    |     1.x      |
|    8.8.x    |   4.x    | 2.0.0-alpha1 |
|    8.8.x    |   4.x    | 2.0.0-alpha1 |
|     9.x     |   4.x    |   8.x-2.1    |
|     9.x     |   5.x    |   8.x-2.2    |
|    10.x     |   4.x    |   8.x-2.2    |
|    10.x     |   5.x    |    3.0.x     |

## Dependencies

The Drupal 10 version of NBSP requires
[Editor](https://www.drupal.org/project/editor) and
[CKEditor 5](https://ckeditor.com/ckeditor-5/).

The Drupal 8 & Drupal 9 versions of NBSP requires
[Editor](https://www.drupal.org/project/editor) and
[CKEditor](https://www.drupal.org/project/ckeditor).

## Supporting organizations

This project is sponsored by Antistatique. We are a Swiss Web Agency,
Visit us at [www.antistatique.net](https://www.antistatique.net) or
[Contact us](mailto:info@antistatique.net).
