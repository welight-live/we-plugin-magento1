<?php
/**
 * welight Transparente Magento
 * Optional Address Attribute model - for configuration purposes
 *
 * @category    Gateway
 * @package     gatewaywelight
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/welight-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class gatewaywelight_Model_Source_Customer_Address_Attributes_Optional
{
    /**
     * Return Address attribute
     * @author Gabriela D'Ávila (http://davila.blog.br)
     * @return array
     */
    public function toOptionArray()
    {
        $fields = Mage::helper('gatewaywelight/internal')->getFields('customer_address');
        $options = array();
        $options[] = array('value'=>'','label'=>'Não Informar ao welight');

        foreach ($fields as $key => $value) {
            if (!is_null($value['frontend_label'])) {
                //caso esteja sendo usado a propriedade multilinha do endereco, ele aceita indicar o que cada linha faz
                if ($value['attribute_code'] == 'street') {
                    $streetLines = Mage::getStoreConfig('customer/address/street_lines');
                    for ($i = 1; $i <= $streetLines; $i++) {
                        $options[] = array('value' => 'street_'.$i, 'label' => 'Street Line '.$i);
                    }
                } else {
                    $options[] = array(
                        'value' => $value['attribute_code'],
                        'label' => $value['frontend_label']. ' (' . $value['attribute_code'] . ')'
                    );
                }
            }
        }

        return $options;
    }
}