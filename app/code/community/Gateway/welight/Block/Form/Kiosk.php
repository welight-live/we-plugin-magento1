<?php
/**
 * welight Transparente Magento
 * Form Kiosk Block Class
 *
 * @category    Gateway
 * @package     gatewaywelight
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2017 Ricardo Martins (http://r-martins.github.io/welight-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class gatewaywelight_Block_Form_Kiosk extends Mage_Payment_Block_Form
{
    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate(false);
    }

}
