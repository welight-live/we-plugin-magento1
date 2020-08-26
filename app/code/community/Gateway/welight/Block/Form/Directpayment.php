<?php
/**
 * welight Transparente Magento
 * Form DirectPayment Block Class
 *
 * @category    Gateway
 * @package     gatewaywelight
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/welight-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class gatewaywelight_Block_Form_Directpayment extends Mage_Core_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('gatewaywelight/form/directpayment.phtml');
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
//        avoids block being inserted twice
        if (false == Mage::registry('directpayment_loaded')) {
            Mage::register('directpayment_loaded', true);
            return parent::_toHtml();
        }

        return '';
    }
}
