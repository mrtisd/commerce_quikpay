<?php

namespace Drupal\commerce_quikpay\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Form\FormStateInterface;

class RedirectCheckoutForm extends PaymentOffsiteForm {

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
	$configuration = $this->getConfiguration();
	
	$orderType = '';
	
	$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
	
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
	$order_id = $payment->getOrderId();
	$currency = $payment->getAmount()->getCurrencyCode();
	$amount = number_format($payment->getAmount()->getNumber(), 2, '', '');
	$total = $amount;
    
	
    $commerce_quikpay_account = $configuration['commerce_quikpay_account'];
    $commerce_quikpay_key = $configuration['commerce_quikpay_key'];
    $commerce_quikpay_order_description = $configuration['commerce_quikpay_order_description'];
    $commerce_quikpay_allowed_methods = $configuration['commerce_quikpay_allowed_methods'];
    $commerce_quikpay_test_url = $configuration['commerce_quikpay_test_url'];
    $commerce_quikpay_live_url = $configuration['commerce_quikpay_live_url'];
    $commerce_quikpay_debug = $configuration['commerce_quikpay_debug'];
    $commerce_quikpay_hash_algo = $configuration['commerce_quikpay_hash_algo'];
    $commerce_quikpay_logging = $configuration['commerce_quikpay_logging'];
    
	$accounts = \Drupal::state()->get('commerce_quikpay_accounts');
	
	$orderType = $accounts[$commerce_quikpay_account];
	
	$hash_method = strtoupper($commerce_quikpay_hash_algo);
	
	$userChoice3 = $order_id;
	$userChoice4 = 'Y';
	$userChoice5 = hash($hash_method, $commerce_quikpay_key . $order_id . $userChoice3 . $total);


	$redirectUrl = $GLOBALS['base_url'] . '/checkout/quikpay/complete';
	$redirectUrlParameters  = "transactionType,transactionStatus,transactionId,originalTransactionId,transactionTotalAmount,transactionDate,transactionAcountType,transactionEffectiveDate,transactionDescription,transactionResultDate,transactionResultEffectiveDate,transactionResultCode,transactionResultMessage,orderNumber,orderType,orderName,orderDescription,orderAmount,orderFee,orderAmountDue,orderDueDate,orderBalance,orderCurrentStatusAmountDue,payerType,payerIdentifier,payerFullName,actualPayerType,actualPayerIdentifier,actualPayerFullName,accountHolderName,streetOne,streetTwo,city,state,zip,country,daytimePhone,eveningPhone,email,userChoice1,userChoice2,userChoice3,userChoice4,userChoice5";
	
	
    // these are all the variables that Nelnet(QuikPay) can accept, in the format [name]:[maxlength]
    $post_vars  = "orderType:32,orderDescription:50,retriesAllowed:1,total:12,amount:12,orderNumber:100,";
    $post_vars .= "redirectUrlParameters:0,userChoice3:50,userChoice4:50,userChoice5:50,redirectUrl:100,email:50";
    
	$data = array();
    
	$data['orderType'] = $orderType;
	$data['orderDescription'] = $commerce_quikpay_order_description;
	$data['retriesAllowed'] = 0;
	$data['total'] = $total;
	$data['amount'] = $total;
	$data['orderNumber'] = $order_id;
	$data['redirectUrlParameters'] = $redirectUrlParameters;
	$data['userChoice3'] = $userChoice3;
	$data['userChoice4'] = $userChoice4;
	$data['userChoice5'] = $userChoice5;
	$data['redirectUrl'] = $redirectUrl;
	$data['email'] = $user->get('mail')->value;;
	
	$vars = explode(',', $post_vars);
    $vars_length = array();
    // initialize variables to be blank strings, this way they won't affect the hash if we don't use them
    foreach ($vars as $key) {
      $key_option = explode(':', $key);
      $vars_length[$key_option[0]] = $key_option[1];
      ${$key_option[0]} = "";
    }
  
	$retriesAllowed = "1";
    
    if($commerce_quikpay_debug==0){
    	$redirect_url = 'https://eservicestest.rit.edu/infinetProcessor/passthroughRedirect.process';
    }else{
    	$redirect_url = 'https://eservices.rit.edu/infinetProcessor/passthroughRedirect.process';
    }
    
    $to_hash = "";
    
    foreach ($vars_length as $key => $value) {
		if ($key != "hash") {
		  $to_hash .= ${$key};
		}
	}

	// QuikPay can be configured to accept MD5 or SHA256, so allow for both
	//$hash = hash($qp_hash_method, $to_hash . $qp_key);
   
	// add each variable to the form as a hidden input

	foreach ($data as $name => $value) {
		$form[$name] = array(
		  '#type' => 'hidden', 
		  '#value' => $value,
		);
	}
	
	return $this->buildRedirectForm(
	  		$form,
	  		$form_state,
	  		$redirect_url,
	  		$data,
	  		PaymentOffsiteForm::REDIRECT_POST
	);
	
  }
  /**
   * @return array
   */
  private function getConfiguration() {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_quikpay\Plugin\Commerce\PaymentGateway\RedirectCheckout $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    return $payment_gateway_plugin->getConfiguration();
  }
  
}