<?php
class Welight_Gateway_Block_Form_Info_Tef extends Mage_Payment_Block_Info
{
    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('welight_gateway/form/info/tef.phtml');
    }
}