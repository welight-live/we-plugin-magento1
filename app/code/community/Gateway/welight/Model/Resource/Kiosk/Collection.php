<?php
/**
 * Class gatewaywelight_Model_Resource_Kiosk_Collection
 *
 * @author    Ricardo Martins <ricardo@magenteiro.com>
 */
class gatewaywelight_Model_Resource_Kiosk_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('gatewaywelight/kiosk');
    }
}