<?php
/**
 * welight Transparente Magento
 * Customer groups - for config purposes
 *
 * @category    Gateway
 * @package     Welight_Gateway
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/welight-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class Welight_Gateway_Model_System_Config_Source_Customer_Groups
{
    /**
     * @return array
     */
    public function toOptionArray ()
    {
        return Mage::getModel('customer/group')->getCollection()
            ->toOptionArray();
    }
}

