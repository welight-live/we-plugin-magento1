<?php
/**
 * welight Transparente Magento
 * Customer DOB Class - for configuration purposes
 *
 * @category    Gateway
 * @package     gatewaywelight
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/welight-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class gatewaywelight_Model_Source_Customer_Dob
{
    /**
     * Returns address attributes
     * @author Gabriela D'Ávila (http://davila.blog.br)
     * @return array
     */
    public function toOptionArray()
    {
        $fields = Mage::helper('gatewaywelight/internal')->getFields('customer');
        $options = array();
        $options[] = array('value'=>'','label'=>'Solicitar ao cliente junto com dados do cartão');

        foreach ($fields as $key => $value) {
            if (!is_null($value['frontend_label'])) {
                $options[] = array(
                    'value' => $value['attribute_code'],
                    'label' => $value['frontend_label'] . ' (' . $value['attribute_code'] . ')'
                );
            }
        }

        return $options;
    }
}