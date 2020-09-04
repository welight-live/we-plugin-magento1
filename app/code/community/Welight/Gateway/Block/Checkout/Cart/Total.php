<?php
/**
 *
 * @category   Inovarti
 * @package    Welight_Gateway
 * @author     Suporte <suporte@inovarti.com.br>
 */
class Welight_Gateway_Block_Checkout_Cart_Total extends Mage_Core_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('welight/checkout/cart/total.phtml');
    }

    public function getAmount()
    {
        $amount = $this->_getQuote()->getBaseGrandTotal();
        $payment = $this->_getPayment();
        if ($payment->getMethod() == 'iugu_cc') {
            $installments = $payment->getInstallments();
            $interestRate = $payment->getMethodInstance()->getInterestRate($installments);
            $installmentAmount = $payment->getMethodInstance()->calcInstallmentAmount($amount, $installments, $interestRate);
            $amount = $installmentAmount * $installments;
        }

        return $amount;
    }

    protected function _getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    protected function _getPayment()
    {
        return $this->_getQuote()->getPayment();
    }

    protected function _toHtml()
    {
        if ($this->getAmount() == $this->_getQuote()->getBaseGrandTotal()) {
            return '';
        }
        return parent::_toHtml();
    }
}
