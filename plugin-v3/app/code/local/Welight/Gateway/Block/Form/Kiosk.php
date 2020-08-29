<?php
/**
 * PagSeguro Transparente Magento
 * Form Kiosk Block Class
 *
 * @category    RicardoMartins
 * @package     Welight_Gateway
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2017 Ricardo Martins (http://r-martins.github.io/PagSeguro-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class Welight_Gateway_Block_Form_Kiosk extends Mage_Payment_Block_Form
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
