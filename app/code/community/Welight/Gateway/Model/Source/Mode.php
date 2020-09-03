<?php
/**
 *
 * @category   Inovarti
 * @package    Welight_Gateway
 * @author     Suporte <suporte@inovarti.com.br>
 */
class Welight_Gateway_Model_Source_Mode
{
    const MODE_TEST = 'test';
    const MODE_LIVE = 'live';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::MODE_TEST,
                'label' => Mage::helper('iugu')->__('Test')
            ),
            array(
                'value' => self::MODE_LIVE,
                'label' => Mage::helper('iugu')->__('Live')
            ),
        );
    }
}
