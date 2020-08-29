<?php
/**
 * PagSeguro Transparente Magento
 * Form CC Block Class
 *
 * @category    RicardoMartins
 * @package     Welight_Gateway
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/PagSeguro-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class Welight_Gateway_Block_Form_Recurring extends Mage_Payment_Block_Form_Cc
{
    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('welight_gateway/form/recurring.phtml');
    }

    /**
     * Insert module's javascript on rendering, only if it wasn't inserted before
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        //adicionaremos o JS do pagseguro na tela que usará o bloco de cartao logo após o <body>
        $head = Mage::app()->getLayout()->getBlock('after_body_start');

        if ($head && false == $head->getChild('js_pagseguro')) {
            $scriptBlock = Mage::helper('welight_gateway')->getPagSeguroScriptBlock();
            $head->append($scriptBlock);
        }

        return parent::_prepareLayout();
    }

    /**
     * Check if Date of Birthday should be visible
     * You can set up it on Payment Methods->PagSeguro Cartão de Crédito
     * @return bool
     */
    public function isDobVisible()
    {
        $ownerDobAttribute = Mage::getStoreConfig('payment/rm_pagseguro_cc/owner_dob_attribute');
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
            $years = Mage::helper('welight_gateway/params')->getYears();
            $years = array(0=>$this->__('Year'))+$years;
            $this->setData('cc_years', $years);
        }

        return $years;
    }

}
