<?php
/**
 * Class gatewaywelight_Block_Form_Info_Kiosk
 *
 * @author    Ricardo Martins
 */
class gatewaywelight_Block_Form_Info_Kiosk extends Mage_Payment_Block_Info
{
    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('gatewaywelight/form/info/kiosk.phtml');
    }

}
