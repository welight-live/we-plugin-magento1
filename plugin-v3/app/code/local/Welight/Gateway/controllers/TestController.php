<?php
/**
 * PagSeguro Transparente Magento
 * Test Controller responsible for diagnostics, usually when you ask for support
 * It helps our team to detect misconfiguration and other problems when you ask for help
 *
 * @category    RicardoMartins
 * @package     Welight_Gateway
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2017 Ricardo Martins (http://r-martins.github.io/PagSeguro-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class Welight_Gateway_TestController extends Mage_Core_Controller_Front_Action
{

    //Disables this controller's actions (this should be enabled only for test/development purposes)
    protected $_disabled = true;

    /**
     * Bring us some information about the module configuration and version info.
     * You can remove it, but can make our team to misjudge your configuration or problem.
     */
    public function getConfigAction()
    {
        $info = array();
        $helper = Mage::helper('welight_gateway');
        $pretty = ($this->getRequest()->getParam('pretty', true) && version_compare(PHP_VERSION, '5.4', '>='))?128:0;

        $info['Welight_Gateway']['version'] = (string)Mage::getConfig()
                                                        ->getModuleConfig('Welight_Gateway')->version;
        $info['Welight_Gateway']['debug'] = Mage::getStoreConfigFlag('payment/rm_pagseguro/debug');
        $info['Welight_Gateway']['sandbox'] = Mage::getStoreConfigFlag('payment/rm_pagseguro/sandbox');
        $info['configJs'] = json_decode($helper->getConfigJs());

        if (Mage::getConfig()->getModuleConfig('Welight_GatewayPro')) {
            $info['Welight_GatewayPro']['version'] = (string)Mage::getConfig()
                                                        ->getModuleConfig('Welight_GatewayPro')->version;

            $keyType = $helper->getLicenseType();
            $info['Welight_GatewayPro']['key_type'] = ($keyType)==''?'assinatura':$keyType;
            $info['Welight_GatewayPro']['key_validation'] = $this->_validateKey();
        }

        $info['compilation'] = $this->_getCompilerState();

        $info['token_consistency'] = $this->_getTokenConsistency();
        $info['session_id'] = $helper->getSessionId();
        $info['jsUrl'] = $helper->getModuleJsUrl(true);
        $info['retry_active'] = $helper->isRetryActive();

        $modules = array_keys((array)Mage::getConfig()->getNode('modules')->children());
        $coreHelper = Mage::helper('core');
        foreach ($modules as $module) {
            if (false !== strpos(strtolower($module), 'pagseguro') && $coreHelper->isModuleEnabled($module)) {
                $info['pagseguro_modules'][] = $module;
            }
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($info, $pretty));

    }

    /**
     * Used to test creditCard form in development phases
     * Disabled by default
     */
    public function standAloneCcAction()
    {
        if ($this->_disabled) {
            Mage::getSingleton('core/session')->addNotice('Route is disabled by default');
            return $this->norouteAction();
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Used to clear credit card hash and session during development
     * Disabled by default
     */
    public function resetCardHashAction()
    {
        if ($this->_disabled) {
            Mage::getSingleton('core/session')->addNotice('Route is disabled by default');
            return $this->norouteAction();
        }

        Mage::getSingleton('checkout/session')->unsetData('PsPayment');
        $this->_redirectReferer();
    }

    /**
     * Validates your PRO key. Only for tests purposes.
     * @return mixed|string
     */
    protected function _validateKey()
    {
        $key = Mage::getStoreConfig('payment/pagseguropro/key');
        if (empty($key)) {
            return 'KEY IS EMPTY';
        }

        $url = 'http://ws.ricardomartins.net.br/pspro/v6/auth/' . $key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        libxml_use_internal_errors(true);

        return curl_exec($ch);
    }

    /**
     * Get compilation config details
     * @return array
     */
    protected function _getCompilerState()
    {
        $compiler = Mage::getModel('compiler/process');
        $compilerConfig = MAGENTO_ROOT . '/includes/config.php';

        if (file_exists($compilerConfig) && !(defined('COMPILER_INCLUDE_PATH') || defined('COMPILER_COLLECT_PATH'))) {
            include $compilerConfig;
        }

        $status = defined('COMPILER_INCLUDE_PATH') ? 'Enabled' : 'Disabled';
        $state  = $compiler->getCollectedFilesCount() > 0 ? 'Compiled' : 'Not Compiled';
        return array(
          'status' => $status,
          'state'  => $state,
          'files_count' => $compiler->getCollectedFilesCount(),
          'scopes_count' => $compiler->getCompiledFilesCount()
        );
    }

    /**
     * @return string
     */
    protected function _getTokenConsistency()
    {
        $token = Mage::helper('welight_gateway')->getToken();
        return (strlen($token)!=32 && strlen($token)!=100)?'Wrong size':'Good';
    }

}
