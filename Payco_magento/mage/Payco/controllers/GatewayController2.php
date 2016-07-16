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
 * @category    Mage
 * @package     Mage_pagosonline
 * @copyright   Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * pagosonline Controller
 * 
 * @category    Mage
 * @package     Mage_Payco
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Payco_GatewayController extends Mage_Core_Controller_Front_Action
{
    /**
     * Get singleton with payment model PagosOnLine Gateway
     *
     * @return Mage_PagosOnLine_Model_Payment_Gateway
     */
    public function getPayment()
    {
        return Mage::getSingleton('Payco/payment_gateway');
    }

    /**
     * Get singleton with model checkout session 
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * When a customer press "Place Order" button on Checkout/Review page 
     * Redirect customer to Pagos On Line payment interface
     * 
     */
    public function payAction()
    {
        $session = $this->getSession();
        $session->setPolGatewayQuoteId($session->getQuoteId());
        $session->setPolGatewayLastRealOrderId($session->getLastRealOrderId());
        
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());

        $payment = $this->getPayment(); 
        $payment->setOrder($order);
        
        $payment->processEventRedirect();
        Mage::register('Payco_payment_gateway', $payment); 
        $this->loadLayout();
        $this->renderLayout();
        
        $session->unsQuoteId();
        $session->unsLastRealOrderId();
    }
    
    /**
     * When a customer successfully returned from Pagos On Line Gateway site 
     * Redirect customer to Checkout/Success page 
     * 
     */
    public function returnSuccessAction()
    {   
        $session = $this->getSession();
        
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getPolGatewayLastRealOrderId());

        if ($order->isEmpty()) {
            return false;
        }
        
        $payment = $this->getPayment(); 
        $payment->setOrder($order);
        $payment->processEventReturnSuccess();
        $session->setPolMessage($this->getRequest()->getParam('mensaje'));
        $session->setPolPayMethod($this->getRequest()->getParam('medio_pago'));
        $session->setPolValor($this->getRequest()->getParam('valor'));
        $session->setPolRef($this->getRequest()->getParam('ref_pol'));
        
        $session->setQuoteId($session->getPolGatewayQuoteId(true));
        $session->getQuote()->setIsActive(false)->save();
        $session->setLastRealOrderId($session->getPolGatewayLastRealOrderId(true));
        //$this->_redirect('checkout/onepage/success');
        $this->_redirect('Payco/gateway/success');
        
    }
    
    /**
     * Enter description here...
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }
     public function successAction()
    {   
        if (!$this->getOnepage()->getCheckout()->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $lastQuoteId = $this->getOnepage()->getCheckout()->getLastQuoteId();
        $lastOrderId = $this->getOnepage()->getCheckout()->getLastOrderId();

        if (!$lastQuoteId || !$lastOrderId) {
            $this->_redirect('checkout/cart');
            return;
        }

        Mage::getSingleton('checkout/session')->clear();
        $this->loadLayout();
        $this->_initLayoutMessages('checkout/session');
        //$this->_initLayoutMessages('pagosonline/session');
        //Mage::dispatchEvent('checkout_onepage_controller_success_action');
        Mage::dispatchEvent('Payco_gateway_controller_success_action');
        $this->renderLayout();
        
    }
    /**
      * Get singleton with Checkout by Amazon order transaction information
     *
     * @return Mage_AmazonPayments_Model_Payment_CBA
     */
    public function getGateway()
    {
        return Mage::getSingleton('Payco/payment_gateway');
    }
   
 	public function responseAction()
    {   
        #$amazonOrderID = Mage::app()->getRequest()->getParam('amznPmtsOrderIds');
        #$referenceId = Mage::app()->getRequest()->getParam('amznPmtsOrderIds');

        $this->getGateway()->returnPol();

        $this->loadLayout();
        $this->_initLayoutMessages('Payco/session');
        $this->renderLayout();
        
    }
    
    /**
     * Customer canceled payment and successfully returned from Pagos On Line Gataway 
     * Redirect customer to Shopping Cart page 
     * 
     */
    public function returnCancelAction()
    {
        $session = $this->getSession();
        $session->setQuoteId($session->getPolGatewayQuoteId(true));
        
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getPolGatewayLastRealOrderId());
        
        if ($order->isEmpty()) {
            return false;
        }

        $payment = $this->getPayment(); 
        $payment->setOrder($order);
        $payment->processEventReturnCancel();
                
        $this->_redirect('checkout/cart/');
    }

    /**
     * Pagos On Line Gateway service send notification 
     * 
     */
    public function notificationAction()
    {
    	 $archivo = 'archivo.txt';
		$fp = fopen($archivo, "a");
		$string = print_r($this->getRequest()->getParams(),true);
		$write = fputs($fp, $string);
		fclose($fp); 
		
        $this->getPayment()->processNotification($this->getRequest()->getParams());
    }
}
