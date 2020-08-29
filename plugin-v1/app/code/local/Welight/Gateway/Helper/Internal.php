<?php
/**
 * welight Transparente Magento
 * Internal Helper Class - responsible for some internal requests
 *
 * @category    Gateway
 * @package     Welight_Gateway
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/welight-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class Welight_Gateway_Helper_Internal extends Mage_Core_Helper_Abstract
{
    /**
     * Get fields from a given entity
     * @author Gabriela D'Ávila (http://davila.blog.br)
     * @param $type
     * @return mixed
     */
    public static function getFields($type = 'customer_address')
    {
        $entityType = Mage::getModel('eav/config')->getEntityType($type);
        $entityTypeId = $entityType->getEntityTypeId();
        $attributes = Mage::getResourceModel('eav/entity_attribute_collection')->setEntityTypeFilter($entityTypeId);

        return $attributes->getData();
    }

    /**
     * Returns associative array with required parameters to API, used on CC method calls
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return array
     */
    public function getCreditCardApiCallParams(Mage_Sales_Model_Order $order, $payment)
    {

        /** @var Welight_Gateway_Helper_Data $helper */
        $helper = Mage::helper('Welight_Gateway');

        $noSID = $helper->isNoSidUrlEnabled();

        /** @var Welight_Gateway_Helper_Params $pHelper */
        $pHelper = Mage::helper('welight_gateway/params'); //params helper - helper auxiliar de parametrização

        $params = array(
        'email'                 => $helper->getMerchantEmail(),
            'token'             => $helper->getToken(),
            'paymentMode'       => 'default',
            'paymentMethod'     =>  'creditCard',
            'receiverEmail'     =>  $helper->getMerchantEmail(),
            'currency'          => 'BRL',
            'creditCardToken'   => $pHelper->getPaymentHash('credit_card_token'),
            'reference'         => $order->getIncrementId(),
            'extraAmount'       => $pHelper->getExtraAmount($order),
            'notificationURL'   => Mage::getUrl(
                'Welight_Gateway/notification',
                array('_secure' => true, '_nosid' => $noSID)
            ),
        );
        $params = array_merge($params, $pHelper->getItemsParams($order));
        $params = array_merge($params, $pHelper->getSenderParams($order, $payment));
        $params = array_merge($params, $pHelper->getAddressParams($order, 'shipping'));
        $params = array_merge($params, $pHelper->getAddressParams($order, 'billing'));
        $params = array_merge($params, $pHelper->getCreditCardHolderParams($order, $payment));
        $params = array_merge($params, $pHelper->getCreditCardInstallmentsParams($order, $payment));

        return $params;
    }

}
