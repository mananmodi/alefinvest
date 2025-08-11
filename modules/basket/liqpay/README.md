###### Change of parameters that go to the payment
```
<?php
function HOOK_liqpay_payment_params_alter(&$params, $payment, &$config){
	
}
```

###### Call upon payment, when the payment is linked to the site
```
<?php
function HOOK_liqpay_api_alter($payment, $fields){

}
```