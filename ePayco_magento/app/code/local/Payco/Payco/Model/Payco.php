<?php


class Payco_Payco_Model_Payco extends Mage_Payment_Model_Method_Abstract {

    /**
     * unique internal payment method identifier
     *
     * @var string [a-z0-9_]
     */
    protected $_code = 'payco';
    protected $_canUseForMultishipping = false;
    protected $_formBlockType = 'payco/form';
    protected $_infoBlockType = 'payco/info';

    /**
     * Return Order place direct url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('payco/payment/redirect', array('_secure' => true));
    }

    public function getGatewayUrl() {
        return  "https://secure.payco.co/checkout.php";
    }

    public function getFormFields() {
        $p_cust_id_cliente = Mage::getStoreConfig('payment/payco/p_cust_id_cliente');
        $p_key = Mage::getStoreConfig('payment/payco/p_key');
        $gateway_url = Mage::getStoreConfig('payment/payco/payco_url');
        if ($gateway_url == "") {
            $gateway_url = "https://secure.payco.co/webcheckout.php";
        }
        $p_test_request = Mage::getStoreConfig('payment/payco/transaction_mode');

        $checkout = Mage::getSingleton('checkout/session');
        $p_id_invoice = $checkout->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($p_id_invoice);

        $p_currency_code = $order->getOrderCurrencyCode();
        //$paymentAmount = $order->getBaseGrandTotal();
        $p_amount = number_format($order->getGrandTotal(), 2, '.', '');
        $p_tax = number_format($order->getTaxAmount(), 2, '.', '');

        $p_base_tax = number_format(($p_amount - $p_tax), 2, '.', '');
        if ($p_tax == 0)
            $p_base_tax = 0;
        //$taxReturnBase = $tax = 0;

        $ProductName = '';
        $items = $order->getAllItems();
        if ($items) {
            foreach ($items as $item) {
                if ($item->getParentItem())
                    continue;
                $ProductName .= $item->getName() . ';';
            }
        }
        $ProductName = rtrim($ProductName, ';');
        $p_signature = md5($p_cust_id_cliente . '^' . $p_key . '^' . $p_id_invoice . '^' . $p_amount . '^' . $p_currency_code);

        //$BAddress = $order->getBillingAddress();

        $extra1 = 'Magento Plugin V 1.9';

        $params = array(
            'p_cust_id_cliente' => $p_cust_id_cliente,
            'p_key' => $p_key,
            'p_id_invoice' => $p_id_invoice,
            'p_description' => $ProductName,
            'p_amount' => $p_amount,
            'p_tax' => $p_tax,
            'p_base_tax' => $p_base_tax,
            'p_signature' => $p_signature,
            'p_currency_code' => $p_currency_code,
            'p_billing_name' => $order->getShippingAddress()->getFirstname(),
            'p_billing_last_name'=>$order->getShippingAddress()->getLastname(),
            'p_billing_email' => $order->getCustomerEmail(),
            'p_billing_address' => $order->getShippingAddress()->getStreet1() . ' ' . $order->getShippingAddress()->getStreet2(),
            'p_billing_city' => $order->getShippingAddress()->getCity(),
            'p_billing_phone' => $order->getShippingAddress()->getTelephone(), 
            'p_test_request' => $p_test_request,
            'p_url_response' => Mage::getUrl('payco/payment/response'),
            'p_url_confirmation' => Mage::getUrl('payco/payment/confirm'),
            'p_extra1' => $extra1,
            
        );
        return $params;
    }

    /**
     * Return true if the method can be used at this time
     *
     * @return bool
     */
    public function isAvailable($quote = null) {
        return true;
    }

}
