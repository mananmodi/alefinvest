# Views Fieldsets

Creates fieldset (and details and div) in Views fields output, to group fields,
by adding a new field: "Global: Fieldset" and a few preprocessors. Also
introduces a new template: views-fieldsets-fieldset.tpl.php where you can
customize your fieldset output.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/admin_menu).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/admin_menu).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers

## Requirements

This module requires no modules outside of Drupal core.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. Navigate to Administration > Extend and enable the module.
2. Navigate to Administration > Structure > views and open existing view or
   a create a new view.
3. Add some fields.
4. Add field "Global: Fieldset" and customize settings (html tag,
   collapsible, tokens etc)
5. Rearrange fields to drag normal fields under Fieldset fields. You can
   nest fieldsets. The result will be visible in Preview.

Theming:
There are several new templates. You can specify the filename the Views way. See
Theme: Information for theme hook suggestion specifics. Available:

- views-fieldsets-fieldset.html.twig
- views-fieldsets-fieldset--events.html.twig
- views-fieldsets-fieldset--default.html.twig (all tags)
- views-fieldsets-fieldset--default.html.twig (per tag)
- views-fieldsets-fieldset--page.html.twig
- views-fieldsets-fieldset--events--page.html.twig

And of course the related preprocessors:

```
template_preprocess_views_fieldsets_fieldset(),
template_preprocess_views_fieldsets_fieldset__events() etc.
```

## Maintainers

- rudiedirkx (rudiedirkx) - https://www.drupal.org/u/rudiedirkx

Supporting organizations:

- GOLEMS GABB (golems-gabb) - https://www.drupal.org/golems-gabb
