<?php
/**
 * PagSeguro Transparente Magento
 * Helper Class - responsible for helping on gathering config information
 *
 * @category    RicardoMartins
 * @package     Welight_Gateway
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/PagSeguro-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class Welight_Gateway_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_PAYMENT_PAGSEGURO_EMAIL              = 'payment/rm_pagseguro/merchant_email';
    const XML_PATH_PAYMENT_PAGSEGURO_TOKEN              = 'payment/rm_pagseguro/token';
    const XML_PATH_PAYMENT_PAGSEGURO_DEBUG              = 'payment/rm_pagseguro/debug';
    const XML_PATH_PAUMENT_PAGSEGURO_SANDBOX            = 'payment/rm_pagseguro/sandbox';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_EMAIL      = 'payment/rm_pagseguro/sandbox_merchant_email';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_TOKEN      = 'payment/rm_pagseguro/sandbox_token';
    const XML_PATH_PAYMENT_PAGSEGURO_WS_URL             = 'payment/rm_pagseguro/ws_url';
    const XML_PATH_PAYMENT_PAGSEGURO_WS_URL_APP         = 'payment/rm_pagseguro/ws_url_app';
    const XML_PATH_PAYMENT_PAGSEGURO_JS_URL             = 'payment/rm_pagseguro/js_url';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_WS_URL     = 'payment/rm_pagseguro/sandbox_ws_url';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_WS_URL_APP = 'payment/rm_pagseguro/sandbox_ws_url_app';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_JS_URL     = 'payment/rm_pagseguro/sandbox_js_url';
    const XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_APPKEY     = 'payment/rm_pagseguro/sandbox_appkey';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_ACTIVE          = 'payment/rm_pagseguro_cc/active';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_FLAG            = 'payment/rm_pagseguro_cc/flag';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_INFO_BRL        = 'payment/rm_pagseguro_cc/info_brl';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_SHOW_TOTAL      = 'payment/rm_pagseguro_cc/show_total';
    const XML_PATH_PAYMENT_PAGSEGUROPRO_TEF_ACTIVE      = 'payment/pagseguropro_tef/active';
    const XML_PATH_PAYMENT_PAGSEGUROPRO_BOLETO_ACTIVE   = 'payment/pagseguropro_boleto/active';
    const XML_PATH_PAYMENT_PAGSEGURO_KEY                = 'payment/pagseguropro/key';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_FORCE_INSTALLMENTS = 'payment/rm_pagseguro_cc/force_installments_selection';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_INSTALLMENT_LIMIT  = 'payment/rm_pagseguro_cc/installment_limit';
    const XML_PATH_PAYMENT_PAGSEGURO_CC_INSTALLMENT_INTEREST_FREE_ONLY =
        'payment/rm_pagseguro_cc/installments_product_interestfree_only';
    const XML_PATH_PAYMENT_PAGSEGURO_NOTIFICATION_URL_NOSID= 'payment/rm_pagseguro/notification_url_nosid';
    const XML_PATH_PAYMENT_PAGSEGURO_PLACEORDER_BUTTON = 'payment/rm_pagseguro/placeorder_button';
    const XML_PATH_JSDELIVR_ENABLED                     = 'payment/rm_pagseguro/jsdelivr_enabled';
    const XML_PATH_JSDELIVR_MINIFY                      = 'payment/rm_pagseguro/jsdelivr_minify';

    /**
     * Returns session ID from PagSeguro that will be used on JavaScript methods.
     * or FALSE on failure
     * @return bool|string
     */
    public function getSessionId()
    {
        if ($fromCache = $this->getSavedSessionId()) {
            return $fromCache;
        }

        $useApp = $this->getLicenseType() == 'app';

        $url = $this->getWsUrl('sessions', $useApp);

        $ch = curl_init($url);
        $params['email'] = $this->getMerchantEmail();
        $params['token'] = $this->getToken();
        if ($useApp) {
            $params['public_key'] = $this->getPagSeguroProKey();
            unset($params['email']);
            unset($params['token']);

            if ($this->isSandbox()) {
                $params['isSandbox'] = '1';
            }
        }

        curl_setopt_array(
            $ch,
            array(
                CURLOPT_POSTFIELDS      => http_build_query($params),
                CURLOPT_POST            => count($params),
                CURLOPT_RETURNTRANSFER  => 1,
                CURLOPT_TIMEOUT         => 45,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_SSL_VERIFYHOST  => false,
                )
        );

        $response = null;

        try{
            $response = curl_exec($ch);
        }catch(Exception $e){
            Mage::logException($e);
            return false;
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response);
        if (false === $xml) {
            if (curl_errno($ch) > 0) {
                $this->writeLog('Falha de comunicação com API do PagSeguro: ' . curl_error($ch));
            } else {
                $this->writeLog(
                    'Falha na autenticação com API do PagSeguro. Verifique email e token cadastrados.
                    Retorno pagseguro: ' . $response
                );
            }

            return false;
        }

        $this->saveSessionId((string)$xml->id);
        return (string)$xml->id;
    }

    /**
     * Saves PagSeguro Session ID in current session to avoid unnecessary calls
     * @param string $sessionId PagSeguro Session Id
     * @return void
     */
    public function saveSessionId($sessionId)
    {
        $sandbox = ($this->isSandbox()) ? '_sandbox' : '';
        Mage::getSingleton('core/session')->setData('rm_pagseguro_sessionid' . $sandbox, $sessionId);

        $time = Mage::getSingleton('core/date')->timestamp();
        Mage::getSingleton('core/session')->setData('rm_pagseguro_sessionid_expires' . $sandbox, $time + 60*10);
    }

    /**
     * Retrieves PagSeguro SessionId from session's cache. Return false if it's not there.
     * @return bool|string
     */
    public function getSavedSessionId()
    {
        $sandbox = ($this->isSandbox()) ? '_sandbox' : '';
        $time = Mage::getSingleton('core/date')->timestamp();
        if (($sessionId = Mage::getSingleton('core/session')->getData('rm_pagseguro_sessionid' .$sandbox))
            && Mage::getSingleton('core/session')->getData('rm_pagseguro_sessionid_expires' .$sandbox) >= $time) {
            return $sessionId;
        }

        return false;
    }

    /**
     * Return merchant e-mail setup on admin
     * @return string
     */
    public function getMerchantEmail()
    {
        if ($this->isSandbox()) {
            return Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_EMAIL);
        }

        return Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_EMAIL);
    }

    /**
     * Returns Webservice URL based on selected environment (prod or sandbox)
     *
     * @param string $amend suffix
     * @param bool $useApp uses app?
     *
     * @return string
     */
    public function getWsUrl($amend='', $useApp = false)
    {
        if ($this->isSandbox()) {
            if ($this->getLicenseType()=='app' && $useApp) {
                return Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_WS_URL_APP) . $amend;
            } else {
                return Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_WS_URL) . $amend;
            }
        }

        if ($this->getLicenseType()=='app' && $useApp) {
            return Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_WS_URL_APP) . $amend;
        }

        return Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_WS_URL) . $amend;
    }

    /**
     * Return PagSeguro's lib url based on selected environment (prod or sandbox)
     * @return string
     */
    public function getJsUrl()
    {
        if ($this->isSandbox()) {
            return Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_JS_URL);
        }

        return Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_JS_URL);
    }

    /**
     * Check if debug mode is active
     * @return bool
     */
    public function isDebugActive()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_PAYMENT_PAGSEGURO_DEBUG);
    }

    /**
     * Is in sandbox mode?
     * @return bool
     */
    public function isSandbox()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_PAUMENT_PAGSEGURO_SANDBOX);
    }

    /**
     * Write something to pagseguro.log
     * @param $obj mixed|string
     */
    public function writeLog($obj)
    {
        if ($this->isDebugActive()) {
            if (is_string($obj)) {
                Mage::log($obj, Zend_Log::DEBUG, 'pagseguro.log', true);
            } else {
                Mage::log(var_export($obj, true), Zend_Log::DEBUG, 'pagseguro.log', true);
            }
        }
    }

    /**
     * Get current decrypted token based on selected environment. Return FALSE if empty.
     * @return string | false
     */
    public function getToken()
    {
        $this->checkTokenIntegrity();
        $token = Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_TOKEN);
        if ($this->isSandbox()) {
            $token = Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_TOKEN);
        }

        if (empty($token)) {
            return false;
        }

        return Mage::helper('core')->decrypt($token);
    }

    /**
     * Check if CPF should be visible with other payment fields
     * @return bool
     */
    public function isCpfVisible()
    {
        $customerCpfAttribute = Mage::getStoreConfig('payment/rm_pagseguro/customer_cpf_attribute');
        return empty($customerCpfAttribute);
    }

    /**
     * Get license type
     * @return string 'app' or ''
     */
    public function getLicenseType()
    {
        $key = Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_KEY);
        if (!$key || strlen($key) <= 6) {
            return '';
        }

        return 'app';
    }

    /**
     * Get PagSeguro PRO key (if exists)
     * @return string
     */
    public function getPagSeguroProKey()
    {
        if ($this->getLicenseType() == 'app' && $this->isSandbox()) {
            return $this->getPagSeguroProSandboxKey();
        }

        return $this->getPagSeguroProNonSandboxKey();
    }

    public function getPagSeguroProSandboxKey()
    {
        return Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_SANDBOX_APPKEY);
    }

    public function getPagSeguroProNonSandboxKey()
    {
        return Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_KEY);
    }

    /**
     * Translate dynamic words from PagSeguro errors and messages
     * @author Ricardo Martins
     * @return string
     */
    public function __()
    {
        $args = func_get_args();
        $expr = new Mage_Core_Model_Translate_Expr(array_shift($args), $this->_getModuleName()); //phpcs:ignore
        array_unshift($args, $expr);

        $text = $args[0]->getText();
        preg_match('/(.*)\:(.*)/', $text, $matches);
        if ($matches!==false && isset($matches[1])) {
            array_shift($matches);
            $matches[0] .= ': %s';
            $args = $matches;
        }

        return Mage::app()->getTranslator()->translate($args);
    }

    /**
     * Check token integrity by verifying it type. If not encrypted, creates a warning on log.
     * @author Ricardo Martins
     * @return void
     */
    public function checkTokenIntegrity()
    {
        $section = Mage::getSingleton('adminhtml/config')->getSection('payment');
        $frontendType = (string)$section->groups->rm_pagseguro->fields->token->frontend_type;

        if ('obscure' != $frontendType) {
            $this->writeLog(
                'O Token não está seguro. Outro módulo PagSeguro pode estar em conflito. Desabilite-os via XML.'
            );
        }
    }

    /**
     * Creates the dynamic parts on module's JS
     * @author Ricardo Martins
     * @return Mage_Core_Block_Text
     */
    public function getPagSeguroScriptBlock()
    {
        $scriptBlock = Mage::app()->getLayout()->createBlock('core/text', 'js_pagseguro');
        $secure = Mage::getStoreConfigFlag('web/secure/use_in_frontend');
        $directPaymentBlock = '';

        if (Mage::app()->getLayout()->getArea() == 'adminhtml') {
            $directPaymentBlock = Mage::app()->getLayout()
                ->createBlock('welight_gateway/form_directpayment')
                ->toHtml();
        }

        $scriptBlock->setText(
            sprintf(
                '
                <script type="text/javascript">var RMPagSeguroSiteBaseURL = "%s";</script>
                <script type="text/javascript" src="%s"></script>
                <script type="text/javascript" src="%s"></script>
                %s
                ',
                Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, $secure),
                Mage::helper('welight_gateway')->getJsUrl(),
                $this->getModuleJsUrl($secure),
                $directPaymentBlock
            )
        );
        return $scriptBlock;
    }

    /**
     * Gets /js/pagseguro.js URL (from this store or from jsDelivr CDNs)
     * @param $secure bool
     *
     * @return string
     */
    public function getModuleJsUrl($secure)
    {
        if (Mage::getStoreConfigFlag(self::XML_PATH_JSDELIVR_ENABLED)) {
            $min = (Mage::getStoreConfigFlag(self::XML_PATH_JSDELIVR_MINIFY)) ? '.min' : '';
            $moduleVersion = (string)Mage::getConfig()->getModuleConfig('Welight_Gateway')->version;
            $url
                = 'https://cdn.jsdelivr.net/gh/r-martins/PagSeguro-Magento-Transparente@%s/js/pagseguro/pagseguro%s.js';
            $url = sprintf($url, $moduleVersion, $min);
            return $url;
        }

        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS, $secure) . 'pagseguro/pagseguro.js';
    }

    /**
     * Retrieves the JS Include for PagSeguro JS only
     * @author Ricardo Martins
     * @return Mage_Core_Block_Text
     */
    public function getExternalPagSeguroScriptBlock()
    {
        $scriptBlock = Mage::app()->getLayout()->createBlock('core/text', 'pagseguro_direct');
        $scriptBlock->setText(
            sprintf(
                '<script type="text/javascript" src="%s" defer></script>',
                Mage::helper('welight_gateway')->getJsUrl()
            )
        );
        return $scriptBlock;
    }

    /**
     * Return serialized (json) string with module configuration
     * return string
     */
    public function getConfigJs()
    {
        $config = array(
            'active_methods' => array(
                'cc' => (int)Mage::getStoreConfigFlag(self::XML_PATH_PAYMENT_PAGSEGURO_CC_ACTIVE),
                'boleto' => (int)Mage::getStoreConfigFlag(self::XML_PATH_PAYMENT_PAGSEGUROPRO_BOLETO_ACTIVE),
                'tef' => (int)Mage::getStoreConfigFlag(self::XML_PATH_PAYMENT_PAGSEGUROPRO_TEF_ACTIVE)
            ),
            'flag' => Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_CC_FLAG),
            'debug' => $this->isDebugActive(),
            'PagSeguroSessionId' => $this->getSessionId(),
            'is_admin' => Mage::app()->getStore()->isAdmin(),
            'show_total' => Mage::getStoreConfigFlag(self::XML_PATH_PAYMENT_PAGSEGURO_CC_SHOW_TOTAL),
            'force_installments_selection' =>
                Mage::getStoreConfigFlag(self::XML_PATH_PAYMENT_PAGSEGURO_CC_FORCE_INSTALLMENTS),
            'installment_limit' => (int)Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_CC_INSTALLMENT_LIMIT),
            'placeorder_button' => Mage::getStoreConfig(self::XML_PATH_PAYMENT_PAGSEGURO_PLACEORDER_BUTTON),
            'loader_url' => Mage::getDesign()->getSkinUrl('pagseguro/ajax-loader.gif', array('_secure'=>true))
        );
        return json_encode($config);
    }

    /**
     * @return string
     */
    public function isInfoBrlActive()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_PAYMENT_PAGSEGURO_CC_INFO_BRL);
    }

    /**
     * Check if order retry is available (PRO module >= 3.3) and enabled
     * @return boolean
     */
    public function isRetryActive()
    {
        $moduleConfig = Mage::getConfig()->getModuleConfig('Welight_GatewayPro');

        if (version_compare($moduleConfig->version, '3.3', '<')) {
            return false;
        }

        $rHelper = Mage::helper('welight_gatewaypro/retry');
        if ($rHelper && $rHelper->isRetryEnabled()) {
            return true;
        }

        return false;
    }

    /**
     * Checks if an order could have retry payment process
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    public function canRetryOrder($order)
    {
        if (!$this->isRetryActive()) {
            return false;
        }

        $paymentMethod = $order->getPayment()->getMethod();
        if ($paymentMethod != 'rm_pagseguro_cc') {
            return false;
        }

        return true;
    }

    /**
     * Checks if "Dont send SID in the Return URL" option is enabled
     * @return bool
     */
    public function isNoSidUrlEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_PAYMENT_PAGSEGURO_NOTIFICATION_URL_NOSID);
    }

    /**
     * Sends information about module's and Magento version
     * @return false|string
     */
    public function getUserAgent()
    {
        $psVersion = (string)Mage::getConfig()->getModuleConfig('Welight_Gateway')->version;
        $psProVersion = 'notInstalled';
        $mageVersion = Mage::getVersion();

        if (Mage::getConfig()->getModuleConfig('Welight_GatewayPro')) {
            $psProVersion = (string)Mage::getConfig()->getModuleConfig('Welight_GatewayPro')->version;
        }

        $userAgent = array('modules' => array('Welight_Gateway'    => $psVersion,
                                              'Welight_GatewayPro' => $psProVersion),
                           'magento' => $mageVersion);
        return json_encode($userAgent);
    }

    /**
     * Adds usage information about platform and module's versions
     * @return array of headers
     */
    public function getCustomHeaders()
    {
        $psVersion = (string)Mage::getConfig()->getModuleConfig('Welight_Gateway')->version;
        $mageVersion = Mage::getVersion();

        $headers = array('Platform: Magento', 'Platform-Version: ' . $mageVersion, 'Module-Version: ' . $psVersion);

        if (Mage::getConfig()->getModuleConfig('Welight_GatewayPro')) {
            $psProVersion = (string)Mage::getConfig()->getModuleConfig('Welight_GatewayPro')->version;
            $headers[] = 'Extra-Version: ' . $psProVersion;
        }

        return $headers;
    }

    /**
     * Checks if IWD Checkout Suite is installed and active
     */
    public function isIwdEnabled()
    {
        return class_exists('IWD_Opc_Helper_Data') && Mage::helper('iwd_opc')->isEnable();
    }

    /**
     * Adds GET params to some url
     *
     * @param string $existingUrl
     * @param array  $params
     */
    public function addUrlParam($existingUrl, $params = array())
    {
        $urlParts = parse_url($existingUrl);
        // If URL doesn't have a query string.
        $existingParams = array();
        if (isset($urlParts['query'])) { // Avoid 'Undefined index: query'
            parse_str($urlParts['query'], $existingParams);
        }

        if (!$existingParams) {
            return  '?' . http_build_query($params);
        }

        return '&' . http_build_query($params);

        /*$parsedSuffix = parse_url($suffix);
        $finalSuffix = '';
        $finalSuffix .= isset($parsedSuffix['path']) ? $parsedSuffix['path'] : '';
        $finalSuffix .= isset($parsedSuffix['query']) ? '?' . $parsedSuffix['query'] : '';

        $query = http_build_query($params);
        $finalSuffix .= (strpos($finalSuffix, '?') === false) ? '?' : '&';

        $prefix = '';
        if (filter_var($suffix, FILTER_VALIDATE_URL) === false) {
            $prefix = isset($parsedSuffix['scheme']) ? $parsedSuffix['scheme'] . '://' : '';
            $prefix .= isset($parsedSuffix['host']) ? $parsedSuffix['host'] : '';
        }

        return $prefix . $finalSuffix . $query;*/
    }
}