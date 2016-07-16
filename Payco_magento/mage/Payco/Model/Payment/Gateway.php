<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Payco
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * PagosOnLine gateway payment Model
 *
 * @category   Mage
 * @package    Mage_Payco
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Payco_Model_Payment_Gateway extends Mage_Payment_Model_Method_Abstract
{
    /**
     * rewrited for Mage_Payment_Model_Method_Abstract 
     */
    protected $_isGateway               = false;
    protected $_canAuthorize            = false;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = true;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_isInitializeNeeded      = true;

    /**
     * rewrited for Mage_Payment_Model_Method_Abstract
     */
    protected $_formBlockType = 'Payco/gateway_form';
    
    /**
     * rewrited for Mage_Payment_Model_Method_Abstract
     */
    protected $_code  = 'Payco_gateway';
    
    /**
     * current order
     */
    protected $_order;

    /**
     * Get value from the module config
     *
     * @param string $path
     * @return string
     */
    public function getConfig($path) 
    {
        return Mage::getStoreConfig('payment/' . $this->_code . '/' . $path);
    }    
    
    /**
     * rewrited for Mage_Payment_Model_Method_Abstract 
     */
    public function isAvailable($quote=null)
    {
        return $this->getConfig('active');
    }

    /**
     * Get singleton with PagosOnLine gateway API Model
     *
     * @return Mage_PagosOnLine_Model_Api_gateway
     */
    public function getApi()
    {
        return Mage::getSingleton('Payco/api_gateway');
    }

    /**
     * Get singleton with PagosOnLine gateway Notification Model
     *
     * @return Mage_PagosOnLine_Model_Payment_gateway_Notification
     */
    public function getNotification()
    {
        return Mage::getSingleton('Payco/payment_gateway_notification');
    }

    /**
     * Set model of current order
     *
     * @param Mage_Sales_Model_Order $order
     * @return Mage_PagosOnLine_Model_Payment_gateway
     */
    public function setOrder($order)
    {
        $this->_order = $order;
        return $this;
    }

    /**
     * Get model of current order
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $paymentInfo = $this->getInfoInstance();
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId(
                $paymentInfo->getOrder()->getRealOrderId()
            );
        }
        return $this->_order;
    }

    /**
     * Add item in to log storage
     *
     * @param string $request
     * @param string $response
     * @return Mage_PagosOnLine_Model_Payment_gateway
     */
    protected function _log($request, $response = '')
    {
        $debug = Mage::getModel('Payco/api_debug')
            ->setRequestBody($request)
            ->setResponseBody($response)
            ->save();
        return $this;
    }

    /**
     * Send mail
     *
     * @param string $template
     * @param array $variables
     * @return Mage_pagosonline_Model_Payment_gateway
     */
    protected function _mail($template, array $variables = array())
    {
        $mailTemplate = Mage::getModel('core/email_template');
        $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->sendTransactional(
                        $this->getConfig($template),
                        $this->getConfig('email_sender_identity'),
                        $this->getConfig('report_email'),
                        null,
                        $variables
                    );  
        return $this;
    }
    
    /**
     * rewrited for Mage_Payment_Model_Method_Abstract 
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('Payco/gateway/pay');
    }

    /**
     * Return Pol payment url
     *
     * @return string
     */
    public function getPayRedirectUrl()
    {
        return $this->getApi()->getPayUrl();
    }

    /**
     * Return pay params for current order
     *
     * @return array
     */
    public function getPayRedirectParams()
    {
        $orderId = $this->getOrder()->getRealOrderId();
        $amount = Mage::app()->getStore()->roundPrice($this->getOrder()->getBaseGrandTotal());
        $currencyCode = $this->getOrder()->getBaseCurrency();
        $iva = Mage::app()->getStore()->roundPrice($this->getOrder()->getTaxAmount());
        
		if($iva == 0)
			$baseDev = 0;
		else {
			$items = $this->getOrder()->getAllItems();
			$baseDev = 0;
			foreach($items as $key => $item){
				if($item->getTaxPercent()!= 0)
					$baseDev = $baseDev + $item->getRowTotal(); 
			}
			
		}
		$emailComprador = $this->getOrder()->getCustomerEmail();
        $urlModel = Mage::getModel('core/url')
            ->setUseSession(false);
        
        return $this->getApi()->getPayParams(
            $orderId, 
            $amount,
            $baseDev,
            $iva, 
            $currencyCode,
            $urlModel->getUrl('Payco/gateway/returnCancel'),
            $urlModel->getUrl('Payco/gateway/returnSuccess'),
            $urlModel->getUrl('Payco/gateway/notification'),
            $this->getDebugValue(),
            $iva,
			$baseDev,
			$emailComprador
            );
    }
    
    public function getDebugValue(){
    	//return $this->getApi()->getDebugValue();
    	return $this->getConfig('debug_log');
    }

    /**
     * When a customer redirect to Pol site 
     * 
     * @return Mage_PagosOnLine_Model_Payment_gateway
     */
    public function processEventRedirect()
    {
        $this->getOrder()->addStatusToHistory(
           $this->getOrder()->getStatus(),
           Mage::helper('Payco')->__('Customer was redirected to Pol site')
        )->save();
        return $this;
    }

    /**
     * When a customer successfully returned from Pol site 
     *
     * @return Mage_PagosOnLine_Model_Payment_gateway 
     */
    public function processEventReturnSuccess()
    {
        $this->getOrder()->addStatusToHistory(
           $this->getOrder()->getStatus(),
           Mage::helper('Payco')->__('Customer successfully returned from Pol site')
        )->save();
        return $this;
    }

    /**
     * Customer canceled payment and successfully returned from Pol site 
     * 
     * @return Mage_PagosOnLine_Model_Payment_gateway
     */
    public function processEventReturnCancel()
    {
        $this->getOrder()->addStatusToHistory(
           $this->getOrder()->getStatus(),
           Mage::helper('Payco')->__('Customer canceled payment and successfully returned from Pol site')
        )->save();
        return $this;
    }

    /**
     * rewrited for Mage_Payment_Model_Method_Abstract 
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_NEW;
        $stateObject->setState($state);
        $stateObject->setStatus(Mage::getSingleton('sales/order_config')->getStateDefaultStatus($state));
        $stateObject->setIsNotified(false);
        return $this;
    }

    /**
     * process Pol notification request
     *
     * @param   array $requestParams
     * @return Mage_PagosOnLine_Model_Payment_gateway
     */
    public function processNotification($requestParams)
    {
        if ($this->getConfig('debug_log')) {
            $this->_log('DEBUG gateway notification: ' . print_r($requestParams, 1));
        }
        
        try {
           $this->getNotification()->setPayment($this)->process($requestParams);
        } catch(Exception $e) {
            if ($this->getConfig('error_log')) {
                $this->_log('ERROR gateway notification: ' . print_r($requestParams, 1), $e->getMessage());
            }

            if ($this->getConfig('report_error_to_email')) {
                $variables = array();
                $variables['request'] = print_r($requestParams, 1); 
                $variables['error'] = $e->getMessage(); 
                $this->_mail('email_template_notofication_error', $variables);
            }
        }
        
        return $this;
    }

    /**
     * rewrited for Mage_Payment_Model_Method_Abstract 
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if (is_null($payment->getCcTransId())) {
            Mage::throwException(
                Mage::helper('Payco')->__('Order was not captured online. Authorization confirmation is required.')
            );
        }
        return $this;
    }

    /**
     * rewrited for Mage_Payment_Model_Method_Abstract 
     */
    public function processInvoice($invoice, $payment)
    {
    	if (!is_null($payment->getCcTransId()) &&
            is_null($payment->getLastTransId()) &&
            is_null($invoice->getTransactionId())) {

            $amount = Mage::app()->getStore()->roundPrice($invoice->getBaseGrandTotal());
            $currencyCode = $payment->getOrder()->getBaseCurrency();
            $transactionId = $payment->getCcTransId();
            $response = $this->getApi()
                ->setStoreId($payment->getOrder()->getStoreId())
                ->capture($transactionId, $amount, $currencyCode);

            if ($response->getStatus() == Mage_PagosOnLine_Model_Api_Gateway_Fps_Response_Abstract::STATUS_ERROR) {
                Mage::throwException(
                    Mage::helper('Payco')->__('Order was not captured. Pol service error: [%s] %s', $response->getCode(), $response->getMessage())
                );
            }

            if ($response->getStatus() == Mage_PagosOnLine_Model_Api_Gateway_Fps_Response_Abstract::STATUS_SUCCESS ||
                $response->getStatus() == Mage_PagosOnLine_Model_Api_Gateway_Fps_Response_Abstract::STATUS_PENDING) {

                $payment->setForcedState(Mage_Sales_Model_Order_Invoice::STATE_OPEN);
                $payment->setLastTransId($response->getTransactionId());

                $invoice->setTransactionId($response->getTransactionId());
                $invoice->addComment(Mage::helper('Payco')->__('Invoice was created (online capture). Waiting for capture confirmation from Pol service.'));

                $payment->getOrder()->addStatusToHistory(
                  $payment->getOrder()->getStatus(),
                  Mage::helper('Payco')->__('Payment was captured online with Pol service. Invoice was created. Waiting for capture confirmation from payment service.')
                )->save();

            }
        }
        return $this;
    }

    /**
     * rewrited for Mage_Payment_Model_Method_Abstract 
     */
    public function processCreditmemo($creditmemo, $payment)
    {

        $transactionId = $creditmemo->getInvoice()->getTransactionId();

        if (!is_null($transactionId) &&
            is_null($creditmemo->getTransactionId())) {

            $amount = Mage::app()->getStore()->roundPrice($creditmemo->getBaseGrandTotal());
            $currencyCode = $payment->getOrder()->getBaseCurrency();
            $referenseID = $creditmemo->getInvoice()->getIncrementId();
            $response = $this->getApi()
                ->setStoreId($payment->getOrder()->getStoreId())
                ->refund($transactionId, $amount, $currencyCode, $referenseID);

            if ($response->getStatus() == Mage_Payco_Model_Api_Gateway_Fps_Response_Abstract::STATUS_ERROR) {
                Mage::throwException(
                    Mage::helper('Payco')->__('Invoice was not refunded. Pol service error: [%s] %s', $response->getCode(), $response->getMessage())
                );
            }

            if ($response->getStatus() == Mage_Payco_Model_Api_Gateway_Fps_Response_Abstract::STATUS_SUCCESS ||
                $response->getStatus() == Mage_Payco_Model_Api_Gateway_Fps_Response_Abstract::STATUS_PENDING) {

                $creditmemo->setTransactionId($response->getTransactionId());
                $creditmemo->addComment(Mage::helper('Payco')->__('Payment refunded online. Waiting for refund confirmation from Pol service.'));
                $creditmemo->setState(Mage_Sales_Model_Order_Creditmemo::STATE_OPEN);

                $payment->getOrder()->addStatusToHistory(
                  $payment->getOrder()->getStatus(),
                  Mage::helper('Payco')->__('Payment refunded online with Pol service. Creditmemo was created. Waiting for refund confirmation from Pol service.')
                )->save();
            }
        }
        return $this;
    }

    /**
     * rewrited for Mage_Payment_Model_Method_Abstract 
     */
    public function cancel(Varien_Object $payment)
    {
        if (!is_null($payment->getCcTransId()) &&
            is_null($payment->getLastTransId())) {

            $transactionId = $payment->getCcTransId();
            $response = $this->getApi()
                ->setStoreId($payment->getOrder()->getStoreId())
                ->cancel($transactionId);

            if ($response->getStatus() == Mage_PagosOnLine_Model_Api_Gateway_Fps_Response_Abstract::STATUS_ERROR) {
                Mage::throwException(
                    Mage::helper('Payco')->__('Order was not cancelled. Pol service error: [%s] %s', $response->getCode(), $response->getMessage())
                );
            }

            if ($response->getStatus() == Mage_PagosOnLine_Model_Api_Gateway_Fps_Response_Abstract::STATUS_CANCELLED) {
                $payment->getOrder()->setState(
                    Mage_Sales_Model_Order::STATE_CANCELED,
                    true,
                    Mage::helper('Payco')->__('Payment authorization cancelled with Pol service.'),
                    $notified = false
                )->save();
            }
        }
        return $this;
    }
    
    /**
     * Return CBA order details in case Html-based shopping cart commited to Amazon
     *
     */
    public function returnPol()
    {
        $_request = Mage::app()->getRequest()->getParams();
        #$_amazonOrderId = Mage::app()->getRequest()->getParam('amznPmtsOrderIds');
        #$_quoteId = Mage::app()->getRequest()->getParam('amznPmtsReqId');

        if ($this->getDebug()) {
            $debug = Mage::getModel('pagosonline/api_debug')
                ->setRequestBody(print_r($_request, 1))
                ->setResponseBody(time().' - success')
                ->save();
        }
    }
}
