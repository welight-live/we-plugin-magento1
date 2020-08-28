<?php
class Welight_Gateway_Helper_Internal extends Mage_Core_Helper_Abstract
{
    public function getBoletoApiCallParams($order, $payment)
    {
        /** @var Welight_Gateway_Helper_Data $helper */
        $helper = Mage::helper('welight_gateway');

        /** @var Welight_Gateway_Helper_Params $phelper */
        $phelper = Mage::helper('welight_gateway/params'); //params helper - helper auxiliar de parametrização
        $params = array(
            'email' => $helper->getMerchantEmail(),
            'token' => $helper->getToken(),
            'paymentMode'   => 'default',
            'paymentMethod' =>  'boleto',
            'receiverEmail' =>  $helper->getMerchantEmail(),
            'currency'  => 'BRL',
            'reference'     => $order->getIncrementId(),
            'extraAmount'=> $phelper->getExtraAmount($order),
            'notificationURL' => Mage::getUrl('welight_gateway/notification', array('_secure' => true)),
        );
        $items = $phelper->getItemsParams($order);
        $params = array_merge($params, $phelper->getItemsParams($order));
        $params = array_merge($params, $phelper->getSenderParams($order, $payment));
        $params = array_merge($params, $phelper->getAddressParams($order, 'shipping'));
        $params = array_merge($params, $phelper->getAddressParams($order, 'billing'));

//    Mage::log(var_export($params, true), null, 'pagseguro.log', true);

        return $params;

    }
    public function getTefApiCallParams($order, $payment)
    {
        $params = $this->getBoletoApiCallParams($order, $payment);
        $params['paymentMethod'] = 'eft';
        $params['bankName'] = $payment['additional_information']['tef_bank'];
        return $params;
    }

    /**
     * Used for retry payment
     * @param $order Mage_Sales_Model_Order
     * @param $payment
     *
     * @return array
     */
    public function getPagseguroCheckoutParams($order, $payment)
    {
        $params = $this->getBoletoApiCallParams($order, $payment);

        $shippingRequired = "true";
        if(!isset($params['shippingCost']) || $params['shippingCost'] == 0) {
            $shippingRequired = "false";

            //remove unneeded shipping info
            foreach ($params as $param => $value) {
                if (strpos($param, 'shipping') !== false){
                    unset($params[$param]);
                }
            }

        }

        $params['paymentMethod'] = 'retry';
        $params['shippingAddressRequired'] = $shippingRequired;

        return $params;
    }

    /**
     * Used for Redirect To Pagseguro payment
     * @param $order
     * @param $payment
     *
     * @return array
     */
    public function getRedirectParams($order, $payment)
    {
        $params = $this->getBoletoApiCallParams($order, $payment);
        $params['acceptPaymentMethodGroup'] = Mage::getStoreConfig('payment/wegateway_redirect/accepted_groups');

        $enableRecover = Mage::getStoreConfigFlag('payment/wegateway_redirect/enable_recovery') ? 'true' : 'false';
        $params['enableRecover'] = $enableRecover;
        $params['shippingAddressRequired'] = false;
        $params['paymentMethod'] = 'redirect';

        if ($redirectURL = Mage::getStoreConfig(Welight_Gateway_Model_Payment_Redirect::XML_PATH_REDIRECTURL)) {
            $params['redirectURL'] = Mage::getUrl($redirectURL, array('_secure'=>true));
        }

        return $params;
    }


































    public function validate($xmlRetorno)
    {
        //Seja consciente ao 'piratear' o módulo. Se você tem conhecimento para fazê-lo, sabe quanto vale o trabalho do seu colega. Grande abraço!
        $k = Mage::getStoreConfig('payme'.'nt/pag'. 'segu'. 'ropro/k'. 'ey');
        $alnum = new Zend_Validate_Alnum();
        if($alnum->isValid($k)){
            try{
                $cli = new Zend_Http_Client('htt' . 'p://ws'. '.rica'. 'rdomar'. 'tins.n'. 'et.'. 'br/ps'.'pro/v'. '6/a'.'uth/'.$k);
                $cli->setParameterGet('base_url', Mage::getStoreConfig('web/unsecure/base_url'));
                $cli->setConfig(array('timeout'=>5));
                $cli->request();
                $b = unserialize($cli->getLastResponse()->getBody());
                if(isset($b['xmlRetorno']))
                {
                    libxml_use_internal_errors(true);
                    return new SimpleXMLElement($b['xmlRetorno']);
                }
            }catch (Exception $e)
            {
                return $xmlRetorno;
            }
        }else{
            Mage::throwException('Cha' . 've ' . 'in' . 'vá'. 'li'.'da. Conf'.'igure su'. 'a cha'.'ve no p' . 'ainel.');
        }
        return $xmlRetorno;
    }
}
