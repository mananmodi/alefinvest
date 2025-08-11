# AlternativeCommerce NovaPoshta API

A free module for the store on Drupal, which will provide the connection of the Nova Poshta delivery service API, which will help you to further expand the functions of your store and set up a delivery system for your customers. AlternativeCommerce module The NovaPoshta API is a complement to the Drupal AlternativeCommerce (Basket) module.

### If you do not need to pull in the list of invoices by the crown

Add to settings.php
```php
$config['novaposhta'] = [
  'disabled.cron.document.list' => TRUE,
  'disabled.cron.area.list' => TRUE,
  'disabled.cron.city.list' => TRUE,
];
```