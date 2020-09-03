<?php
/**
 *
 * @category   Inovarti
 * @package    Welight_Gateway
 * @author     Suporte <suporte@inovarti.com.br>
 */
class Welight_Gateway_Block_Checkout_Success_Payment_Boleto extends Welight_Gateway_Block_Checkout_Success_Payment_Default
{
    public function getInvoiceUrl()
    {
        return $this->getPayment()->getIuguPdf();
    }
}
