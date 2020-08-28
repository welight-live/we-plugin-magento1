<?php
class Welight_Gateway_Model_Payment_Tef extends Welight_Gateway_Model_Abstract
{
    protected $_code = 'wegateway_tef';
    protected $_formBlockType = 'welight_gateway/form_tef';
    protected $_infoBlockType = 'welight_gateway/form_info_tef';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;


    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();

        /** @var Welight_Gateway_Helper_Params $pHelper */
        $pHelper = Mage::helper('welight_gateway/params');

        $info->setAdditionalInformation('sender_hash', $pHelper->getPaymentHash('sender_hash'));
        $info->setAdditionalInformation('tef_bank', $data->getPagseguroproTefBank());
        if (Mage::helper('welight_gateway')->isCpfVisible()) {
            $info->setAdditionalInformation($this->getCode() . '_cpf', $data->getData($this->getCode() . '_cpf'));
        }

        return $this;
    }


    public function order(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();
        $helper = Mage::helper('welight_gateway/internal');
        $rmHelper = Mage::helper('welight_gateway');

        //montaremos os dados a ser enviados via POST pra api em $params
        $params = $helper->getTefApiCallParams($order, $payment);

        //chamamos a API
        $xmlRetorno = $this->callApi($params, $payment);
        $xmlRetorno = $helper->validate($xmlRetorno);
//        $this->proccessNotificatonResult($xmlRetorno);

        if (isset($xmlRetorno->errors)) {
            foreach ($xmlRetorno->errors as $error) {
                $errMsg[] = $rmHelper->__((string)$error->message) . ' (' . $error->code . ')';
            }
            Mage::throwException('Um ou mais erros ocorreram no seu pagamento.' . PHP_EOL . implode(PHP_EOL, $errMsg));
        }

        if (isset($xmlRetorno->error)) {
            $error = $xmlRetorno->error;
            $errMsg[] = $rmHelper->__((string)$error->message) . ' (' . $error->code . ')';

            if(count($xmlRetorno->error) > 1){
                unset($errMsg);
                foreach ($xmlRetorno->error as $error) {
                    $errMsg[] = $rmHelper->__((string)$error->message) . ' (' . $error->code . ')';
                }
            }

            Mage::throwException('Um erro ocorreu em seu pagamento.' . PHP_EOL . implode(PHP_EOL, $errMsg));
        }

        $payment->setSkipOrderProcessing(true);

        if (isset($xmlRetorno->code)) {
            $payment->setAdditionalInformation(array('transaction_id'=>(string)$xmlRetorno->code));
        }

        if (isset($xmlRetorno->paymentMethod->type) && (int)$xmlRetorno->paymentMethod->type == 3) {
           $payment->setAdditionalInformation('tefUrl', (string)$xmlRetorno->paymentLink);
        }

        return $this;

    }
}