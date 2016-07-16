<?php

class Payco_Payco_Block_Form extends Mage_Payment_Block_Form
{

    /**
     * Varien constructor
     */
    protected function _construct()
    {
        $this->setTemplate('payco/form.phtml');
        parent::_construct();
    }

}
