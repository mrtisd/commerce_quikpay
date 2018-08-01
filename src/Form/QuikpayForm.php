<?php
/**
 * @file
 * Contains \Drupal\commerce_quikpay\Form\QuikpayForm.
 */

namespace Drupal\commerce_quikpay\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;

/**
 * Implements an example form.
 */
class QuikpayForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_quikpay_form';
  }

  /**
   * {@inheritdoc}
  */
   public function buildForm(array $form, FormStateInterface $form_state) {
	$accounts = \Drupal::state()->get('commerce_quikpay_accounts');

	for ($i=0;$i<count($accounts);$i++) {
		$form["commerce_quikpay_accounts_$i"] = [
		  '#type'           =>  'textfield',
		  '#title'          =>  $this->t('QuikPay Account #@id', array('@id' => $i+1)),
		  '#default_value'  =>  $accounts[$i],
		];
	}

	$form['add_new_account'] = [
		'#type'             =>  'button',
		'#value'            =>   $this->t('Add New Account'),
	];
	
	if(count($accounts)>1){
		$form['remove_last_account'] = [
			'#type'             =>  'button',
			'#value'            =>   $this->t('Remove Last Account'),
		];
	}
	
	$form_state->setCached(FALSE);
    $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
    ];
	

	return $form;
  }

	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		$triggerdElement = $form_state->getTriggeringElement();
		$htmlIdofTriggeredElement = $triggerdElement['#id'];
		
		if ($htmlIdofTriggeredElement == 'edit-add-new-account') {
			$accounts = \Drupal::state()->get('commerce_quikpay_accounts');
			$accounts[] = " ";
			\Drupal::state()->set('commerce_quikpay_accounts', $accounts);
		}
		if ($htmlIdofTriggeredElement == 'edit-remove-last-account') {
			$accounts = \Drupal::state()->get('commerce_quikpay_accounts');
			
			unset ($accounts[count($accounts)-1]);
			//$accounts = array_pop($accounts);
			\Drupal::state()->set('commerce_quikpay_accounts', $accounts);
			
		}
	}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	$accounts = array();
	foreach ($form_state->getValues() as $key => $value) {
		if (substr($key, 0, 25) == "commerce_quikpay_accounts") {
			$accounts[] = $value;
		}
	}
	
	\Drupal::state()->set('commerce_quikpay_accounts', $accounts);
	
	drupal_set_message(t("Configuration settings saved."));
  }

}