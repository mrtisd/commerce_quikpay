<?php
namespace Drupal\commerce_quikpay\Plugin\Commerce\PaymentGateway;


use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the QuikPay offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "quikpay_redirect_checkout",
 *   label = @Translation("QuikPay (Redirect to quikpay)"),
 *   display_label = @Translation("QuikPay"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_quikpay\PluginForm\RedirectCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 * )
 */
class RedirectCheckout extends OffsitePaymentGatewayBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'commerce_quikpay_account' => '',
      'commerce_quikpay_key' => '',
      'commerce_quikpay_order_description' => '',
      'commerce_quikpay_allowed_methods' => '',
      'commerce_quikpay_test_url' => '',
      'commerce_quikpay_live_url' => '',
      'commerce_quikpay_debug' => '',
      'commerce_quikpay_hash_algo' => '',
      'commerce_quikpay_logging' => '',
    ] + parent::defaultConfiguration();
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    
	$commerce_quikpay_account = $this->configuration['commerce_quikpay_account'];
	$commerce_quikpay_key = $this->configuration['commerce_quikpay_key'];
	$commerce_quikpay_order_description = $this->configuration['commerce_quikpay_order_description'];
	$commerce_quikpay_allowed_methods = $this->configuration['commerce_quikpay_allowed_methods'];
	//$commerce_quikpay_test_url = $this->configuration['commerce_quikpay_test_url'];
	//$commerce_quikpay_live_url = $this->configuration['commerce_quikpay_live_url'];
	$commerce_quikpay_debug = $this->configuration['commerce_quikpay_debug'];
	$commerce_quikpay_hash_algo = $this->configuration['commerce_quikpay_hash_algo'];
	$commerce_quikpay_logging = $this->configuration['commerce_quikpay_logging'];
	
	if (empty($commerce_quikpay_account)) {
		$commerce_quikpay_account = "";
	}
	if (empty($commerce_quikpay_key)) {
		$commerce_quikpay_key = "";
	}
	if (empty($commerce_quikpay_order_description)) {
		$commerce_quikpay_order_description = "";
	}
	if (empty($commerce_quikpay_allowed_methods)) {
		$commerce_quikpay_allowed_methods = array('cc' => 0, 'ach' => 0);
	}
	if (empty($commerce_quikpay_test_url)) {
		$commerce_quikpay_test_url = "";
	}
	if (empty($commerce_quikpay_live_url)) {
		$commerce_quikpay_live_url = "";
	}
	if (empty($commerce_quikpay_debug)) {
		$commerce_quikpay_debug = "0";
	}
	if (empty($commerce_quikpay_hash_algo)) {
		$commerce_quikpay_hash_algo = "md5";
	}
	if (empty($commerce_quikpay_logging)) {
		$commerce_quikpay_logging = array(1 => 0, 2 => 0, 4 => 0);
	}
	
	$accounts = \Drupal::state()->get('commerce_quikpay_accounts');
	
	$form['commerce_quikpay_account'] = [
		'#type'           =>  'radios',
		'#title'          =>  $this->t('Account ID'),
		'#options'        =>  $accounts,
		'#default_value'  =>  $commerce_quikpay_account,
		'#required'       =>  TRUE,
	];

	$form['commerce_quikpay_key'] = [
		'#type'           =>  'textfield',
		'#title'          =>  $this->t('Security Key'),
		'#default_value'  =>  $commerce_quikpay_key,
		'#required'       =>  TRUE,
	];
	$form['commerce_quikpay_order_description'] = [
		'#type' => 'textfield',
		'#title' => $this->t('Order Description'),
		'#default_value' => $commerce_quikpay_order_description,
		'#description' => $this->t('This is the description of the order that gets passed through to Nelnet.'),
	];
	$form['commerce_quikpay_allowed_methods'] = [
		'#type'           =>  'checkboxes',
		'#title'          =>  $this->t('Allowed Payment Methods'),
		'#options'        =>  array(
			'ach'             =>  $this->t('eCheck'),
			'cc'              =>  $this->t('Credit Card'),
		),
		'#default_value'  =>  $commerce_quikpay_allowed_methods,
		'#required'       =>  TRUE,
	];

	/*$form['commerce_quikpay_test_url'] = [
		'#type'           =>  'textfield',
		'#title'          =>  $this->t('Test URL'),
		'#default_value'  =>  $commerce_quikpay_test_url,
		'#required'       =>  TRUE,
	];

	$form['commerce_quikpay_live_url'] = [
		'#type'           =>  'textfield',
		'#title'          =>  $this->t('Live URL'),
		'#default_value'  =>  $commerce_quikpay_live_url,
		'#required'       =>  TRUE,
	];*/

	$form['commerce_quikpay_debug'] = [
		'#title'          =>  'Debug Mode (Live vs Test URL)',
		'#type'           =>  'radios',
		'#options'        =>  array(
			0                 =>  $this->t('Test'),
			1                 =>  $this->t('Production'),
		),
		'#default_value'  =>  $commerce_quikpay_debug,
		'#required'       =>  TRUE,
	];

	$form['commerce_quikpay_hash_algo'] = [
		'#type'           =>  'radios',
		'#title'          =>  $this->t('Hashing Method'),
		'#options'        =>  array(
			'md5'             =>  $this->t('MD5'),
			'sha256'          =>  $this->t('SHA256'),
		),
		'#default_value'  =>  $commerce_quikpay_hash_algo,
		'#required'       =>  TRUE,
	];

	$form['commerce_quikpay_logging'] = [
		'#type'           =>  'checkboxes',
		'#title'          =>  $this->t('Logging Options'),
		'#options'        =>  array(
			1 	=>  $this->t('Log all variables'),
			2   =>  $this->t('Log QuikPay postback data'),
			4   =>  $this->t('Log data posted to QuikPay'),
		),
		'#default_value'  =>  $commerce_quikpay_logging,
	];

	return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
	parent::submitConfigurationForm($form, $form_state);
	if (!$form_state->getErrors()) {
		$values = $form_state->getValue($form['#parents']);
	  
		$this->configuration['commerce_quikpay_account'] = $values['commerce_quikpay_account'];
		$this->configuration['commerce_quikpay_key'] = $values['commerce_quikpay_key'];
		$this->configuration['commerce_quikpay_order_description'] = $values['commerce_quikpay_order_description'];
		$this->configuration['commerce_quikpay_allowed_methods'] = $values['commerce_quikpay_allowed_methods'];
		//$this->configuration['commerce_quikpay_test_url'] = $values['commerce_quikpay_test_url'];
		//$this->configuration['commerce_quikpay_live_url'] = $values['commerce_quikpay_live_url'];
		$this->configuration['commerce_quikpay_debug'] = $values['commerce_quikpay_debug'];
		$this->configuration['commerce_quikpay_hash_algo'] = $values['commerce_quikpay_hash_algo'];
		$this->configuration['commerce_quikpay_logging'] = $values['commerce_quikpay_logging'];
		
    }
  }
 

  

}