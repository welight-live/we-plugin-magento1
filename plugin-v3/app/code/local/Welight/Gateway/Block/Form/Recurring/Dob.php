<?php

class Welight_Gateway_Block_Form_Recurring_Dob extends Mage_Customer_Block_Widget_Dob
{
    public function _construct()
    {
        parent::_construct();

        // default template location | caminho do template de data de nascimento
        $this->setTemplate('welight_gateway/form/recurring/dob.phtml');
        $this->setFieldNameFormat('payment[ps_recurring_owner_birthday_%s]');
        $this->setFieldIdFormat('pagseguro_ps_recurring_owner_birthday_%s');
    }
}
