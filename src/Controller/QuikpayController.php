<?php

namespace Drupal\commerce_quikpay\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\state_machine\Event\WorkflowTransitionEvent;

/**
 * Endpoints for the routes defined.
 */
class QuikpayController extends ControllerBase {
  /**
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;
  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  
  /**
   * Constructor.
   *
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(RequestStack $request_stack, EntityTypeManagerInterface $entityTypeManager) {
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entityTypeManager;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }
  /**
   * Callback action.
   *
   * Listen for callbacks from QuikPay and creates any payment specified.
   *
   * @param Request $request
   *
   * @return Response
   */
  public function callback(Request $request) {
    $orderNumber = $this->requestStack->getCurrentRequest()->query->get('orderNumber');
	$transactionStatus = $this->requestStack->getCurrentRequest()->query->get('transactionStatus');
	$transactionId = $this->requestStack->getCurrentRequest()->query->get('transactionId');
	
	if ($transactionStatus!=1) {
        throw new PaymentGatewayException('Payment failed!');
    }
	
    $order = Order::load($orderNumber);
    
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'Accepted',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => 'nelnet',
      'order_id' => $order->id(),
      'remote_id' => $transactionId,
      'remote_state' => $transactionStatus,
    ]);

    $payment->save();
    
    // I prefer the "throw-it-all-at-the-wall-and-see-what-sticks" method
      $transition = $order->getState()->getWorkflow()->getTransition('place');
      $order->getState()->applyTransition($transition);
      $order->save();
    
    return array(
	  '#markup' => '<p>Payment has been received for order #' . $orderNumber . ', and your order is now complete.</p>',
	);
  }

  /**
   * Get the state from the transaction.
   *
   * @param object $content
   *   The request data from QuickPay.
   *
   * @return string
   */
  private function getRemoteState($content) {
    $latest_operation = end($content->operations);
    return $latest_operation->qp_status_msg;
  }
}

