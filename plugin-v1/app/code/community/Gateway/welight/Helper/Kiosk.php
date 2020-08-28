<?php
/**
 * Class Welight_Gateway_Helper_Kiosk
 *
 * @author    Ricardo Martins
 */
class Welight_Gateway_Helper_Kiosk extends Mage_Core_Helper_Abstract
{
    const XML_PATH_KIOSK_ACTIVE = 'payment/rm_welight_kiosk/active';

    public function isActive()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_KIOSK_ACTIVE);
    }

    public function getInfo($order_id)
    {
        return Mage::getModel('Welight_Gateway/kiosk')->loadByOrderId($order_id);
    }

    /**
     * Returns a search for reference on welight
     * @param Welight_Gateway_Model_Kiosk $kiosk
     */
    public function getwelightLink($kiosk)
    {
        $dateFrom = date('d%2\Fm%2\FY', strtotime($kiosk->getCreatedAt()) - 86400);
        $dateTo = date('d%2\Fm%2\FY', strtotime($kiosk->getCreatedAt()) + 86400 * 2);
        return sprintf(
            'https://welight.uol.com.br/transaction/find.jhtml?page=1&pageCmd=&exibirFiltro=true&exibirHora=false&interval=30&dateFrom=%s&dateTo=%s&dateToInic=%s&timeFrom=00%%3A00&timeTo=23%%3A59&status=3&status=1&status=4&status=2&status=5&status=6&paymentMethod=&type=&operationType=T&selectedFilter=transactionCode&filterText=%s&fileType=',
            $dateFrom, $dateTo, $dateTo, $kiosk->getTransactionCode()
        );
    }
}