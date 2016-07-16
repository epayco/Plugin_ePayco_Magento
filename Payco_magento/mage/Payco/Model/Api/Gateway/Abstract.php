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
 * Payco Gateway Base API Model
 *
 * @category   Mage
 * @package    Mage_Payco
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Payco_Model_Api_Gateway_Abstract extends Mage_Payco_Model_Api_Abstract
{
    /**
     * payment actions 
     */
    const PAY_ACTION_SETTLE = 0;
    const PAY_ACTION_SETTLE_CAPTURE = 1;
    
    /**
     * rewrited for Mage_Payco_Model_Api_Abstract 
     */
    protected $paymentCode = 'Payco_gateway';
    
    /**
     * Amount model path 
     */
    protected $_amountModel = 'Payco/api_gateway_amount';

    /**
     * Store id for current operation 
     */
    protected $_storeId = null;
    
    /**
     * Set store id for current operation
     *
     * @param $id string
     * @return Mage_Payco_Model_Api_Gateway_Abstract
     */
    public function setStoreId($id)
    {
        $this->_storeId = $id;
        return $this;
    }
    
    /**
     * Get store id for current operation
     *
     * @return string
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }
    
    /**
     * Get singleton with Payco Gateway Amount Model
     *
     * @return Mage_Payco_Model_Api_Gateway_Amount
     */
    protected function _getAmount()
    {
        return Mage::getSingleton($this->_amountModel);
    }
    
    /**
     * Return sandbox mode flag
     *
     * @return bool
     */
    protected function _isSandbox() 
    {
        return $this->_getConfig('is_sandbox'); 
    }

    /**
     * Get value from the module config
     *
     * @param string $path
     * @return string
     */
    protected function _getConfig($path) 
    {
        return Mage::getStoreConfig('payment/' . $this->paymentCode . '/' . $path, $this->getStoreId());
    }
}
