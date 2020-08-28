<?php
/**
 * PagSeguro Transparente Magento
 *
 * @package     Welight_Gateway
 * @copyright   Copyright (c) 2019
 * @author      Ricardo Martins <pagseguro-transparente@ricardomartins.net.br>
 * @license     https://opensource.org/licenses/GPL-2.0
 */
class Welight_Gateway_Model_Source_Redirect_Groups
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array(
            array('value' => 'CREDIT_CARD',    'label' => 'Cartões de Crédito'),
            array('value' => 'BOLETO', 'label' => 'Boleto')
        );

        return $options;
    }
}

