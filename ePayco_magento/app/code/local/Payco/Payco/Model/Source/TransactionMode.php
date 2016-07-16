<?php
class Payco_Payco_Model_Source_TransactionMode
{
    public function toOptionArray()
    {
        $options =  array();   
        $options[] = array(
            	   'value' => 'TRUE',
            	   'label' => 'Test'
         );
		 $options[] = array(
            	   'value' => 'FALSE',
            	   'label' => 'Production'
         );

        return $options;
    }
}