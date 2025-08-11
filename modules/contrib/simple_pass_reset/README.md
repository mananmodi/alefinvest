# Simple Password Reset

Simple Password Reset is a module for Drupal which changes the user experience 
when resetting their password. Also setting the password the first time.

Without this module, Drupal emails the user a link to a "one-time login" page.
On that page, they click "Log in" and on the next page they may edit their 
password.

With this module, Drupal emails a link and on that page the user may edit 
his/her password. That is, the "one-time login" page is skipped entirely.

This is useful because it streamlines the process. Also, many users find the 
one-time login page confusing and unexpected. So not needing that is a good 
thing.

The idea for this module comes from [Dave Cohen](https://www.drupal.org/u/dave-cohen).

For a full description of the module, visit the
[project page](https://www.drupal.org/project/simple_pass_reset).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/simple_pass_reset).

## Requirements

This module requires no modules outside of Drupal core.

## Installation

Enable the module as you would any other Drupal module.

On install, this module will adjust its own weight to 1 (instead of 0).
This is so our form_alter hooks act after system.module's. If for any reason 
your system.module (or, any module that alters the password form) has a weight 
higher than 0, you may want to manually change the weight of this module to be 
even higher.

## Configuration

This module works out-of-the-box but allows to set a login redirection if needed
at `/admin/config/people/accounts/simple_pass_reset`.

## Usage tips

This module also provides an option to show a "brief" version of the password 
reset form. So the user is prompted only to change their password. This 
eliminates the confusion many users experience with the current process. To 
enable the brief form, you must update the email bodies in your site's account 
settings form to change any instance of [user:one-time-login-url] to 
[user:one-time-login-url]/brief. (This feature available only in D7 version. 
In D8, by default it provides only password field alone)

If you are using any CAPTCHA related modules to protect forms on your site, we 
recommend disabling the CAPTCHA elements on the password reset form, especially 
if your users must clear a CAPTCHA to request a new password to begin with. The 
only accommodation this module currently makes is to not hide form elements 
added to the brief password reset form of the captcha element type defined by 
the CAPTCHA module.
