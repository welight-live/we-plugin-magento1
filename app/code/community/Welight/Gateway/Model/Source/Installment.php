<?php
/**
 * @category    Inovarti
 * @package     Welight_Gateway
 * @copyright   Copyright (c) 2014 Inovarti. (http://www.inovarti.com.br)
 */
class Welight_Gateway_Model_Source_Installment
{
    public function toOptionArray()
    {
        $options = array();
        for ($i=2; $i <= 12; $i++) {
            $options[] = array('value' => $i, 'label' => $i . 'x');
        }
        return $options;
    }
}
