<?php
/**
 * Class Welight_Gateway_Block_Form_Info_Kiosk
 *
 * @author    Ricardo Martins
 */
class Welight_Gateway_Block_Form_Info_Kiosk extends Mage_Payment_Block_Info
{
    /**
     * Set block template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Welight_Gateway/form/info/kiosk.phtml');
    }

}
