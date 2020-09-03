<?php
/**
 *
 * @category   Inovarti
 * @package    Welight_Gateway
 * @author     Suporte <suporte@inovarti.com.br>
 */
class Welight_Gateway_Model_Boleto extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'iugu_boleto';

    protected $_formBlockType = 'iugu/form_boleto';
    protected $_infoBlockType = 'iugu/info_boleto';

    protected $_isGateway                   = true;
    protected $_canUseForMultishipping      = false;
    protected $_isInitializeNeeded          = true;
    protected $_canUseInternal              = false;

    public function assignData($data)
    {
        $info = $this->getInfoInstance();
        $info->setInstallments(null)
            ->setInstallmentDescription(null)
            ->setIuguBoletoName($data->getIuguBoletoName())
            ->setIuguBoletoCpfCnpj($data->getIuguBoletoCpfCnpj())
        ;
        return $this;
    }

    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $this->_place($payment, $order->getBaseTotalDue());
        return $this;
    }

    public function _place(Mage_Sales_Model_Order_Payment $payment, $amount)
    {
        $order = $payment->getOrder();
        $items = Mage::helper('iugu')->getItemsFromOrder($payment->getOrder());
        $payer = Mage::helper('iugu')->getPayerInfoFromOrder($payment->getOrder());
        $payer->setName($payment->getIuguBoletoName());
        $payer->setCpfCnpj($payment->getIuguBoletoCpfCnpj());

        $data = new Varien_Object();
        $data->setMethod(Welight_Gateway_Model_Api::PAYMENT_METHOD_BOLETO)
            ->setEmail($order->getCustomerEmail())
            ->setItems($items)
            ->setPayer($payer)
            ->setNotificationUrl(Mage::getUrl('iugu/notification'));

        // Discount
        if ($order->getBaseDiscountAmount()) {
            $data->setDiscountCents(Mage::helper('iugu')->formatAmount(abs($order->getBaseDiscountAmount())));
        }

        // Tax
        if ($order->getBaseTaxAmount()) {
            $data->setTaxCents($this->formatAmount($order->getBaseTaxAmount()));
        }

        $result = Mage::getSingleton('iugu/api')->charge($data);
        if ($result->getErrors()) {
            $messages = array();
            if (is_array($result->getErrors())) {
                foreach ($result->getErrors() as $field => $errors) {
                    foreach ($errors as $error) {
                        $messages[] = $field . ': ' . $error . '.';
                    }
                }
            } else {
                $messages[] = $result->getErrors();
            }
            Mage::throwException(implode("\n", $messages));
        }

        // iugu info
        $payment->setIuguInvoiceId($result->getInvoiceId())
            ->setIuguUrl($result->getUrl())
            ->setIuguPdf($result->getPdf())
        ;

        return $this;
    }
}
