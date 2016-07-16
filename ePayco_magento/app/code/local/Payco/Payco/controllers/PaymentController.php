<?php

class Payco_Payco_PaymentController extends Mage_Core_Controller_Front_Action {

    public function redirectAction()
    {
        $payco = Mage::getModel('payco/payco');
        
        $fields = $payco->getFormFields();

        $form = new Varien_Data_Form();
        $form->setAction( $payco->getGatewayUrl() )
            ->setId('payco_checkout')
            ->setName('payco_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);
			
	foreach ($fields as $field=>$value) {
                $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
        }
        $html = '<html><body><center><div style="margin-top:20px">';
        $html.= $this->__('You will be directed to the secure.payco.co in a few seconds.');
        $html.= $form->toHtml();
		$html.= '<img src="https://369969691f476073508a-60bf0867add971908d4f26a64519c2aa.ssl.cf5.rackcdn.com/logos/logo_epayco_200px.png" alt="Enviando">';
        $html.= '<script type="text/javascript">document.getElementById("payco_checkout").submit();</script>';
        $html.= '<center></div></body></html>';

        echo $html;
    }
	
	public function responseAction()
	{		
		
		$x_respuesta=$_POST['x_response'];
        $x_cod_response=$_POST['x_cod_response'];
        $x_transaction_id=$_POST['x_transaction_id'];
        $x_approval_code=1;//$_POST['x_approval_code'];
		
		if($x_respuesta=='Aceptada' && $x_cod_response=='1' &&  $x_approval_code!='000000'){
				$this->_redirect('checkout/onepage/success');
		} else {
				$this->_redirect('checkout/onepage/failure');
		}

	}
	
	public function confirmAction()
	{
		$x_respuesta=$_POST['x_response'];
        $x_cod_response=$_POST['x_cod_response'];
        $x_transaction_id=$_POST['x_transaction_id'];
        $x_approval_code=$_POST['x_approval_code'];
        $x_id_invoice=$_POST['x_id_invoice'];
        $x_ref_payco=$_POST['x_ref_payco'];
		$x_response_reason_text=$_POST['x_response_reason_text'];
        $order = Mage::getModel('sales/order')->loadByIncrementId($x_id_invoice);
       
		$order_comment = "";

		foreach($_POST as $key=>$value){
			$order_comment .= "<br/>$key: $value";
		}
		if($order->getStatus()=='complete'){
			echo 'Transacción ya procesada';
			exit;
		}

		if($x_respuesta=='Aceptada'  && $x_cod_response=='1'){
				
				$order->getPayment()->setTransactionId($x_ref_payco);	
				$order->getPayment()->registerCaptureNotification($_POST['x_amount'] );
				$order->addStatusToHistory($order->getStatus(), $order_comment);
				$order->save();
				echo utf8_encode('Transacción Aceptada');
                    
		} else {
			
                if($x_respuesta=='Pendiente'){
                	$order->addStatusToHistory('pending', $order_comment);
                	echo utf8_encode('Transacción Pendiente');
                }
               	if($x_respuesta=='Rechazada' || $x_respuesta=='Fallida'){
               		$order = Mage::getModel('sales/order')->loadByIncrementId($x_id_invoice);
                	$order->cancel();
                	$order->addStatusToHistory($order->getStatus(), $order_comment);
                	$order->save();
                	echo utf8_encode('Transacción Rechazada');
               	} 
		}
		exit;

	}
	
}