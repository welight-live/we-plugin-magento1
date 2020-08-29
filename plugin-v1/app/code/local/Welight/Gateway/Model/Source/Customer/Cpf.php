<?php
/**
 * welight Transparente Magento
 * Customer CPF possible fields mapping
 *
 * @category    Gateway
 * @package     Welight_Gateway
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/welight-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class Welight_Gateway_Model_Source_Customer_Cpf
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $fields = Mage::helper('welight_gateway/internal')->getFields('customer');
        $options = array();
        $options[] = array('value'=>'','label'=>'Solicitar junto com os outros dados do pagamento');

        foreach ($fields as $key => $value) {
            if (!is_null($value['frontend_label'])) {
                $options['customer|'.$value['frontend_label']] = array(
                    'value' => 'customer|'.$value['attribute_code'],
                    'label' => 'Customer: '.$value['frontend_label'] . ' (' . $value['attribute_code'] . ')'
                );
            }
        }

        $addressFields = Mage::helper('welight_gateway/internal')->getFields('customer_address');
        foreach ($addressFields as $key => $value) {
            if (!is_null($value['frontend_label'])) {
                $options['address|'.$value['frontend_label']] = array(
                    'value' => 'billing|'.$value['attribute_code'],
                    'label' => 'Billing: '.$value['frontend_label'] . ' (' . $value['attribute_code'] . ')'
                );
            }
        }

        return $options;
    }
}
