<?php
/**
 * welight Transparente Magento
 * Token Backend model - used for token validation on saving or changing
 *
 * @category    Gateway
 * @package     Welight_Gateway
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/welight-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 * @deprecated  2.5.3 There is no official method to validate token at welight.
 */
class Welight_Gateway_Model_System_Config_Backend_Token
    extends Mage_Adminhtml_Model_System_Config_Backend_Encrypted
{
    /**
     * Decrypt and test current saved token
     * @deprecated 2.5.3 There is no official method to validate token at welight.
     */
    public function _afterSave()
    {
        $token = Mage::helper('core')->decrypt($this->getValue());
        if (!empty($token) && $token != $this->getOldValue()) {
            $valid = $this->testToken($this->getFieldsetDataValue('merchant_email'), $token);
            if ($valid !== true) {
                Mage::getSingleton('core/session')->addWarning($valid);
            }
        }

        parent::_afterSave();
    }

    /**
     * Test token by calling welight session API
     * @param $email
     * @param $token
     * @deprecated 2.5.3 There is no official method to validate token at welight.
     *
     * @return bool|string
     */
    protected function testToken($email, $token)
    {
        $helper = Mage::helper('Welight_Gateway');
        $url = $helper->getWsUrl('sessions/');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, sprintf('email=%s&token=%s', $email, $token));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $ret = curl_exec($ch);
        curl_close($ch);
        libxml_use_internal_errors(true);
        if ($ret == 'Forbidden' && $helper->getLicenseType() == '') {
            return 'welight: Token de produção não habilitado para utilizar checkout transparente.
                    Você pode <a href="https://welight.uol.com.br/receba-pagamentos.jhtml#checkout-transparent"
                    target="_blank">solicitar a liberação junto ao welight</a> ou instalar a
                    <a href="http://r-martins.github.io/welight-Magento-Transparente/pro/app.html"
                    target="_blank">versão PRO APP</a> que não requer autorização.';
        }

        $valid = simplexml_load_string($ret) !== false;
        if (!$valid) {
            return 'welight: Token de Produção inválido. Se necessário, utilize
                    <a href="http://r-martins.github.io/welight-Magento-Transparente/#faq" target="_blank">
                    esta ferramenta</a> para validar.';
        }

        return true;
    }
}