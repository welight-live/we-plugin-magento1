<?php
/**
 * welight Transparente Magento
 * Form CC Block Class
 *
 * @category    Gateway
 * @package     Welight_Gateway
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/welight-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class Welight_Gateway_Block_Form_Cc extends Mage_Payment_Block_Form_Cc
{
    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Welight_Gateway/form/cc.phtml');
    }

    /**
     * Insert module's javascript on rendering, only if it wasn't inserted before
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        //adicionaremos o JS do welight na tela que usará o bloco de cartao logo após o <body>
        $head = Mage::app()->getLayout()->getBlock('after_body_start');

        if ($head && false == $head->getChild('js_welight')) {
            $scriptBlock = Mage::helper('Welight_Gateway')->getwelightScriptBlock();
            $head->append($scriptBlock);
        }

        return parent::_prepareLayout();
    }

    /**
     * Check if Date of Birthday should be visible
     * You can set up it on Payment Methods->welight Cartão de Crédito
     * @return bool
     */
    public function isDobVisible()
    {
        $ownerDobAttribute = Mage::getStoreConfig('payment/rm_welight_cc/owner_dob_attribute');
        return empty($ownerDobAttribute);
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        if (null === $years) {
            $years = Mage::helper('Welight_Gateway/params')->getYears();
            $years = array(0=>$this->__('Year'))+$years;
            $this->setData('cc_years', $years);
        }

        return $years;
    }

}
