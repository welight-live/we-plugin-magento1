<?php
/**
 * welight Transparente Magento
 * Ajax Controller responsible for module's ajax requests
 *
 * @category    Gateway
 * @package     Welight_Gateway
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/welight-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class Welight_Gateway_AjaxController extends Mage_Core_Controller_Front_Action
{

    /**
     * Returns the order grand total, used to get installments of CC method
     */
    public function getGrandTotalAction()
    {
        //if nominal, there's no installment to be calculated. This will just shut down the ajax attempts
        $total = true;

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::helper('checkout/cart')->getQuote();
        if (!$quote->isNominal()) {
            $quote->getPayment()->setMethod('rm_welight_cc');
            $quote->collectTotals();
            $total = $quote->getGrandTotal();
        }

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode(array('total'=>$total)));
    }

    /**
     * Return session Id from welight, based on merchant e-mail and token
     * Double check your e-mail and token at http://r-martins.github.io/welight-Magento-Transparente/#faq
     */
    public function getSessionIdAction()
    {
        $_helper = Mage::helper('welight_gateway');
        $sessionId = $_helper->getSessionId();

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode(array('session_id' => $sessionId)));
    }

    public function updatePaymentHashesAction()
    {
        $paymentPost = $this->getRequest()->getPost('payment');
        $isAdmin = isset($paymentPost['is_admin']) && $paymentPost['is_admin']=="true";
        $session = 'checkout/session';
        if ($isAdmin) {
            $session = 'core/cookie';
            Mage::getSingleton($session)->set('PsPayment', Zend_Serializer::serialize($paymentPost));
        } else {
            Mage::getSingleton($session)->setData('PsPayment', Zend_Serializer::serialize($paymentPost));
        }

        $this->getResponse()->setHttpResponseCode(200);
    }
}
