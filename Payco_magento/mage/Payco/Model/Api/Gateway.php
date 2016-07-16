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
 * Payco Gateway API Model
 *
 * @category   Mage
 * @package    Mage_Payco
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Payco_Model_Api_Gateway extends Mage_Payco_Model_Api_Gateway_Abstract
{
    /**
     * collect shipping address to IPN notification request 
     */
    protected $_collectShippingAddress = 0;

    /**
     * IPN notification request model path 
     */
    protected $_ipnRequest = 'Payco/api_gateway_ipn_request';
    
    /**
     * FPS model path 
     */
    protected $_fpsModel = 'Payco/api_gateway_fps';

    /**
     * Get singleton with Payco gateway API FPS Model
     *
     * @return Mage_Payco_Model_Api_gateway_Fps
     */
    protected function _getFps()
    {
        return Mage::getSingleton($this->_fpsModel)->setStoreId($this->getStoreId());
    }
    
    /**
     * Get singleton with Payco gateway IPN notification request Model
     *
     * @return Mage_Payco_Model_Api_gateway_Ipn_Request
     */
    protected function _getIpnRequest()
    {
        return Mage::getSingleton($this->_ipnRequest);
    }
    
    
    /**
     * Return Pagos On Line Gateway url
     *
     * @return string
     */
    public function getPayUrl () 
    {
        return $this->_getConfig('pay_service_url');
    } 
    
    /**
     * Return Pagos On Line Gateway params
     *
     * @param string $referenceId
     * @param string $amountValue
     * @param string $currencyCode
     * @param string $abandonUrl
     * @param string $returnUrl
     * @param string $ipnUrl
     * @return array
     */
    public function getPayParams($referenceId, $amountValue, $baseDev, $iva, $currencyCode, $abandonUrl, $returnUrl, $ipnUrl, $debug, $iva, $baseDev, $emailComprador, $lng) 
    {	
        $amount = Mage::getSingleton('Payco/api_gateway_amount')
            ->setValue($amountValue)
            ->setCurrencyCode($currencyCode->currency_code);
        
        $prueba = $this->_getConfig('sitio_prueba');
        if($prueba == 1){
            $prueba = "TRUE";
        }else if($prueba == 0){
            $prueba = "FALSE";
        }

        $requestParams = array();
        $requestParams['p_cust_id_cliente'] =  $this->_getConfig('access_key');
        $requestParams['p_key'] = sha1($this->_getConfig('secret_key').$this->_getConfig('access_key'));
		
        $requestParams['p_id_factura'] = $referenceId; 
        $requestParams['p_amount'] = $amountValue;
        $requestParams['p_description'] = "ORDEN DE COMPRA # ".$referenceId;
        $requestParams['p_email'] = $emailComprador;
        $requestParams['p_tax'] = $iva;
        $requestParams['p_currency_code'] = $currencyCode->currency_code;
        $requestParams['p_url_respuesta'] = $returnUrl;
        $requestParams['p_amount_base'] = $baseDev;
         $requestParams['p_test_request'] = $prueba;
         $requestParams['p_extra1'] = "0";
         $requestParams['p_extra2'] = "0";
         $requestParams['p_extra3'] = "0";


        return $requestParams;
    }
    
    public function getDebugValue(){
    	return $this->_getConfig('error_log');
    }	
    
    /**
     * process notification request
     *
     * @param array $requestParams
     * @return Mage_Payco_Model_Api_gateway_Ipn_Request
     */
    public function processNotification($requestParams) 
    {   
        $requestSignature = false;
        
        if (isset($requestParams['firma'])) {
            $requestSignature = $requestParams['firma'];
            unset($requestParams['firma']);
        }
        /*
        $originalSignature = $this->_getSignatureForArray($requestParams, $this->_getConfig('secret_key'));
        if ($requestSignature != $originalSignature) {
            Mage::throwException(Mage::helper('Payco')->__('Request signed an incorrect or missing signature'));
        }
        */
    	
        $ipnRequest = $this->_getIpnRequest();
        
        if(!$ipnRequest->init($requestParams)) {
            Mage::throwException(Mage::helper('Payco')->__('Request is not a valid IPN request'));
        }
        
        return $ipnRequest;
    }

    /**
     * cancel payment through FPS api
     *
     * @param string $transactionId
     * @return Mage_Payco_Model_Api_gateway_Fps_Response_Abstract
     */
    public function cancel($transactionId) 
    {
        $fps = $this->_getFps();

        $request = $fps->getRequest(Mage_Payco_Model_Api_Gateway_Fps::ACTION_CODE_CANCEL)
            ->setTransactionId($transactionId)
            ->setDescription($this->_getConfig('cancel_description'));
            
        $response = $fps->process($request);
        return $response; 
    }
    
    /**
     * capture payment through FPS api
     *
     * @param string $transactionId
     * @param string $amount
     * @param string $currencyCode
     * @return Mage_Payco_Model_Api_gateway_Fps_Response_Abstract
     */
    public function capture($transactionId, $amount, $currencyCode) 
    {
        $fps = $this->_getFps();
        $amount = $this->_getAmount()
            ->setValue($amount)
            ->setCurrencyCode($currencyCode);
                        
        $request = $fps->getRequest(Mage_Payco_Model_Api_Gateway_Fps::ACTION_CODE_SETTLE)
            ->setTransactionId($transactionId)
            ->setAmount($amount);

        $response = $fps->process($request);
        return $response; 
    }

    /**
     * capture payment through FPS api
     *
     * @param string $transactionId
     * @param string $amount
     * @param string $currencyCode
     * @param string $referenceId
     * @return Mage_Payco_Model_Api_Gateway_Fps_Response_Abstract
     */
    public function refund($transactionId, $amount, $currencyCode, $referenceId) 
    {
        $fps = $this->_getFps();

        $amount = $this->_getAmount()
            ->setValue($amount)
            ->setCurrencyCode($currencyCode);
        
        $request = $fps->getRequest(Mage_Payco_Model_Api_Gateway_Fps::ACTION_CODE_REFUND)
            ->setTransactionId($transactionId)
            ->setReferenceId($referenceId)
            ->setAmount($amount)
            ->setDescription($this->_getConfig('refund_description'));

        $response = $fps->process($request);
        return $response; 
    }
}
