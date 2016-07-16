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
 * Payco Gateway IPN notification request Model
 *
 * @category   Mage
 * @package    Mage_Payco
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Payco_Model_Api_Gateway_Ipn_Request extends Varien_Object
{
    /*
     * Status request 
     */
    const STATUS_CANCEL_CUSTOMER = '5'; 
    const STATUS_CANCEL_TRANSACTION = '5'; 
    const STATUS_RESERVE_SUCCESSFUL = '7'; 
    const STATUS_PAYMENT_INITIATED = '2'; 
    const STATUS_PAYMENT_SUCCESSFUL = '4'; 
    const STATUS_PAYMENT_FAILED = '6'; 
    const STATUS_REFUND_SUCCESSFUL = '8'; 
    const STATUS_REFUND_FAILED = '8'; 
    const STATUS_SYSTEM_ERROR = '6'; 
    
    /*
     * Request params 
     */
    private $requestParams;
    
    /**
     * Init object 
     *
     * @param array $requestParams
     * @return Mage_Payco_Model_Api_Gateway_Ipn_Request
     */
    public function init($requestParams)
    {
        if (!$this->_validateRequestParams($requestParams)) {
            return false;
        }
        $this->requestParams = $requestParams;
        $this->_setRequestParamsToData($this->_convertRequestParams($requestParams));
        
        return $this;
    }
    
    /**
     * Validate request params 
     *
     * @param array $requestParams
     * @return bool
     */
    private function _validateRequestParams($requestParams)
    {
        if (!isset($requestParams['valor']) ||
            !isset($requestParams['fecha_transaccion']) ||
            !isset($requestParams['estado_pol'])) {
                return false;
        }
        
        $statusCode = $requestParams['estado_pol'];
        
        if ($statusCode != self::STATUS_CANCEL_CUSTOMER &&
            $statusCode != self::STATUS_CANCEL_TRANSACTION &&
            $statusCode != self::STATUS_RESERVE_SUCCESSFUL &&
            $statusCode != self::STATUS_PAYMENT_INITIATED &&
            $statusCode != self::STATUS_PAYMENT_SUCCESSFUL &&
            $statusCode != self::STATUS_PAYMENT_FAILED &&
            $statusCode != self::STATUS_REFUND_SUCCESSFUL &&
            $statusCode != self::STATUS_REFUND_FAILED &&
            $statusCode != self::STATUS_SYSTEM_ERROR) {
                return false;
        }

        if ($statusCode != self::STATUS_CANCEL_TRANSACTION &&
            !isset($requestParams['fecha_transaccion'])){
            return false;        
        }
        
        if (($statusCode == self::STATUS_RESERVE_SUCCESSFUL ||
             $statusCode == self::STATUS_PAYMENT_SUCCESSFUL ||
             $statusCode == self::STATUS_REFUND_SUCCESSFUL) &&
             !isset($requestParams['ref_pol'])) {
                return false;
        }
             
        if (!$this->_convertAmount($requestParams['valor'], $requestParams['moneda'])) {
            return false;
        }

        if ($requestParams['estado_pol'] == self::STATUS_REFUND_SUCCESSFUL ||
            $requestParams['estado_pol'] == self::STATUS_REFUND_FAILED) {
            if (!$this->_convertReferenceId($requestParams['ref_venta'])) {
                return false;
            }
            
        }
       
        return true;
    }

    /**
     * convert request params 
     *
     * @param array $requestParams
     * @return array
     */
    private function _convertRequestParams($requestParams)
    {
        $_tmpResultArray = $this->_convertAmount($requestParams['valor'], $requestParams['moneda']);
        unset($requestParams['valor']);
        $requestParams = array_merge($requestParams, $_tmpResultArray); 

        if ($requestParams['estado_pol'] == self::STATUS_REFUND_SUCCESSFUL ||
            $requestParams['estado_pol'] == self::STATUS_REFUND_FAILED) {
          
           // $requestParams['ref_venta'] = $this->_convertReferenceId($requestParams['ref_venta']);
        }

        //$requestParams['fecha_transaccion'] = $this->_convertTransactionDate($requestParams['fecha_transaccion']);
        
        return $requestParams;
    }
    
    
    /**
     * convert union amount string to amount value and amount currency code
     *
     * @param string $requestAmount
     * @return Mage_Payco_Model_Api_Gateway_Amount
     */
    private function _convertAmount($requestAmount, $requestCurrency) 
    {
        $amount = Mage::getSingleton('Payco/api_gateway_amount');
        if (!$amount->init($requestAmount, $requestCurrency)) {
            return false;
        }
        
        $resultArray = array(); 
        $resultArray['valor'] = $amount->getValue();
        $resultArray['moneda'] = $amount->getCurrencyCode();
        return $resultArray;
    }

    /**
     * convert peferenceId request param
     *
     * @param string $referenceId
     * @return string
     */
    private function _convertReferenceId($referenceId) 
    {
        $tmpArr = array();
        if (!preg_match("/^Refund\sfor\s([0-9]{9})$/", $referenceId, $tmpArr)) {
            return false;
        }
        return $tmpArr[1];
    }

    /**
     * convert TransactionDate request param
     *
     * @param string $transactionDate
     * @return string
     */
    private function _convertTransactionDate($transactionDate) 
    {
        return Mage::app()->getLocale()->date($transactionDate);
    }    

    /**
     * set request params to object date
     *
     * @param array $requestParams
     */
    private function _setRequestParamsToData($requestParams)
    {
        foreach ($requestParams as $kay => $value) {
            $setMethodName = 'set' . ucfirst($kay); 
            $this->$setMethodName($value);
        }
    }
    
    /**
     * rewrited for Varien_Object 
     */
    public function toString($format='')
    {
        $resultString = '';
        foreach($this->getData() as $kay => $value){
            $resultString .= "[$kay] = $value<br/>"; 
        }
        return $resultString;
    }
}
