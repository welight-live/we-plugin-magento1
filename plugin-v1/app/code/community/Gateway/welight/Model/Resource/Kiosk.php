<?php
/**
 * Class Welight_Gateway_Model_Resource_Kiosk
 *
 * @author    Ricardo Martins <ricardo@magenteiro.com>
 */
class Welight_Gateway_Model_Resource_Kiosk extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('Welight_Gateway/kiosk', 'temporary_order_id');
    }
}