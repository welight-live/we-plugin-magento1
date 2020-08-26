<?php
/**
 * For viewing orders in older versions < 3.0
 * Class gatewaywelight_Model_Payment_Ccview
 *
 * @author    Ricardo Martins <ricardo@Gateway.net.br>
 */
class gatewaywelight_Model_Payment_Ccview extends gatewaywelight_Model_Payment_Cc
{
    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        return false;
    }
}
