<?php
/**
 * @category    Inovarti
 * @package     Welight_Gateway
 * @copyright   Copyright (c) 2014 Inovarti. (http://www.inovarti.com.br)
 */
class Welight_Gateway_Model_Source_Cctype extends Mage_Payment_Model_Source_Cctype
{
    public function getAllowedTypes()
    {
        return array('VI', 'MC', 'AE', 'DC');
    }

    public function getTypeByBrand($brand)
    {
        $brand = strtolower($brand);
        $data = array(
            'visa'          => 'VI',
            'mastercard'    => 'MC',
            'amex'          => 'AE',
            'diners'        => 'DC',
        );

        $type = isset($data[$brand]) ? $data[$brand] : null;
        return $type;
    }
}
