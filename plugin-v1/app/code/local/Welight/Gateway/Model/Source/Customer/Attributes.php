<?php
/**
 * welight Transparente Magento
 * Customer Attributes source, used for config
 *
 * @category    Gateway
 * @package     Welight_Gateway
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/welight-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class Welight_Gateway_Model_Source_Customer_Attributes
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $fields = Mage::helper('welight_gateway/internal')->getFields('customer');
        $options = array();

        foreach ($fields as $key => $value) {
            if (!is_null($value['frontend_label'])) {
                $options[$value['frontend_label']] = array(
                    'value' => $value['attribute_code'],
                    'label' => $value['frontend_label'] . ' (' . $value['attribute_code'] . ')'
                );
            }
        }

        return $options;
    }
}