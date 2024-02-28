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
        /*foreach ($fields as $field=>$value) {
            $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
        }*/
        $p_cust_id_cliente=$fields["p_cust_id_cliente"];
        $p_key=$fields["p_key"];
        $p_test_request=$fields["p_test_request"];
        $p_description=$fields["p_description"];
        $p_id_invoice=$fields["p_id_invoice"];
        $p_amount = $fields["p_amount"];
        $p_tax = $fields["p_tax"];
        $p_currency_code= strtolower($fields["p_currency_code"]);
        $p_base_tax = $fields["p_base_tax"];
        $country = strtolower($fields["country"]);
        $p_response = $fields["p_url_response"];
        $p_confirmation =  $fields["p_url_confirmation"];
        $p_billing_email =  $fields["p_billing_email"];
        $p_billing_fullname = $fields["fullname"];
        $p_addres_billing = $fields["p_billing_address"];
        $lang = 'es';
        $xternal = 'true';
        $html = '<html><body><center><div style="margin-top:20px">';
        $html.= $this->__('You will be directed to the ePayco in a few seconds ... .');
        $html.= $form->toHtml();
        $html.=" 
                <script src=\"https://checkout.epayco.co/checkout.js\"></script>
            <script>
                var handler = ePayco.checkout.configure({
                    key: \"{$p_key}\",
                    test: \"{$test}\"
                })
                var date = new Date().getTime();
                var data = {
                    name: \"{$p_description}\",
                    description: \"{$p_description}\",
                    invoice: \"{$p_id_invoice}\",
                    currency: \"{$p_currency_code}\",
                    amount: \"{$p_amount}\".toString(),
                    tax_base: \"{$p_base_tax}\".toString(),
                    tax: \"{$p_tax}\".toString(),
                    taxIco: \"0\",
                    country: \"{$country}\",
                    lang: \"{$lang}\",
                    external: \"{$xternal}\",
                    confirmation: \"{$p_confirmation}\",
                    response: \"{$p_response}\",
                    address_billing: \"{$p_addres_billing}\",
                    email_billing: \"{$p_billing_email}\",
                    extras_epayco: {extra5:\"p26\"}
                }
                handler.open(data)
            </script>
 
                    </form>";

        // $html.= '<script type="text/javascript">document.getElementById("payco_checkout").submit();</script>';
        $html.= '<center></div></body></html>';

        echo $html;
    }

    public function responseAction()
    {
        $x_approval_code=1;//$_POST['x_approval_code'];
        $explode=explode('?',$_GET['order_id']);
        $strref_payco=explode("=",$explode[1]);
        $ref_payco=$_REQUEST['ref_payco'];
        $url = 'https://secure.epayco.co/validation/v1/reference/'.$ref_payco;
        $responseData = $this->agafa_dades($url,false,$this->goter());
        $jsonData = @json_decode($responseData, true);
        $validationData = $jsonData['data'];
        $ref_payco = $validationData['x_ref_payco'];
        $x_respuesta=$_POST['x_response']??$validationData['x_response'];
        $x_cod_response=$_POST['x_cod_response']??$validationData['x_cod_response'];
        $x_transaction_id=$_POST['x_transaction_id']??$validationData['x_transaction_id'];
        if($x_respuesta=='Aceptada' || $x_respuesta=='Pendiente'){
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
    
    public function agafa_dades($url) {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            $timeout = 5;
            $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
            curl_setopt($ch,CURLOPT_MAXREDIRS,10);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        }else{
            $data =  @file_get_contents($url);
            return $data;
        }
    }
    public function goter(){
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'protocol_version' => 1.1,
                'timeout' => 10,
                'ignore_errors' => true
            )
        ));
    }

}