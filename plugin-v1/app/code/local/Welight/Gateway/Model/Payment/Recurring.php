<?php
/**
 * welight Transparente Magento
 * Model CC Class - responsible for credit card payment processing
 *
 * @category    Gateway
 * @package     Welight_Gateway
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/welight-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class Welight_Gateway_Model_Payment_Recurring extends Welight_Gateway_Model_Recurring
    implements Mage_Payment_Model_Recurring_Profile_MethodInterface
{
    protected $_code = 'rm_welight_recurring';
    protected $_formBlockType = 'Welight_Gateway/form_recurring';
    protected $_infoBlockType = 'Welight_Gateway/form_info_recurring';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;
    protected $_canCreateBillingAgreement   = true;



    /**
     * Check if module is available for current quote and customer group (if restriction is activated)
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $isAvailable = parent::isAvailable($quote);
        if (empty($quote)) {
            return $isAvailable;
        }

        $helper = Mage::helper('Welight_Gateway');
        $useApp = $helper->getLicenseType() == 'app';
        if (!$useApp || !$quote->isNominal()) {
            return false;
        }

        $helper = Mage::helper('Welight_Gateway/recurring');
        $lastItem = $quote->getItemsCollection()->getLastItem();
        if (!$lastItem->getId()) {
            return false;
        }
        
        $product = $lastItem->getProduct();
        $profile = $product->getRecurringProfile();
        $welightPeriod = $helper->getwelightPeriod($profile);

        if (false == $welightPeriod || $profile['start_date_is_editable']) {
            return false;
        }

        if ($isAvailable) {
            return true;
        }

        return false;
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();

        /** @var Welight_Gateway_Helper_Params $pHelper */
        $pHelper = Mage::helper('Welight_Gateway/params');

        $info->setAdditionalInformation('sender_hash', $pHelper->getPaymentHash('sender_hash'))
            ->setAdditionalInformation('credit_card_token', $pHelper->getPaymentHash('credit_card_token'))
            ->setAdditionalInformation('credit_card_owner', $data->getPsCcOwner())
            ->setCcType($pHelper->getPaymentHash('cc_type'))
            ->setCcLast4(substr($data->getPsCcNumber(), -4));

        //cpf
        if (Mage::helper('Welight_Gateway')->isCpfVisible()) {
            $info->setAdditionalInformation($this->getCode() . '_cpf', $data->getData($this->getCode() . '_cpf'));
        }

        //DOB
        $ownerDobAttribute = Mage::getStoreConfig('payment/rm_welight_cc/owner_dob_attribute');
        if (empty($ownerDobAttribute)) {
            $info->setAdditionalInformation(
                'credit_card_owner_birthdate',
                date(
                    'd/m/Y',
                    strtotime(
                        $data->getPsCcOwnerBirthdayYear().
                        '/'.
                        $data->getPsCcOwnerBirthdayMonth().
                        '/'.$data->getPsCcOwnerBirthdayDay()
                    )
                )
            );
        }

        return $this;
    }

    /**
     * Validate payment method information object
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        parent::validate();

        /** @var Welight_Gateway_Helper_Data $helper */
        $helper = Mage::helper('Welight_Gateway');

        /** @var Welight_Gateway_Helper_Params $pHelper */
        $pHelper = Mage::helper('Welight_Gateway/params');

        $shippingMethod = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingMethod();

        // verifica se não há método de envio selecionado antes de exibir o erro de falha no cartão de crédito - Weber
        if (empty($shippingMethod)) {
            return false;
        }

        $senderHash = $pHelper->getPaymentHash('sender_hash');
        $creditCardToken = $pHelper->getPaymentHash('credit_card_token');

        //mapeia a request URL atual
        $controller = Mage::app()->getRequest()->getControllerName();
        $action = Mage::app()->getRequest()->getActionName();
        $route = Mage::app()->getRequest()->getRouteName();
        $pathRequest = $route.'/'.$controller.'/'.$action;

        //seta os paths para bloqueio de validação instantânea definidos no admin no array
        $configPaths = Mage::getStoreConfig('payment/rm_welight/exception_request_validate');
        $configPaths = preg_split('/\r\n|[\r\n]/', $configPaths);

        //Valida token e hash se a request atual se encontra na lista de
        //exceções do admin ou se a requisição vem de placeOrder
        if ((!$creditCardToken || !$senderHash) && !in_array($pathRequest, $configPaths)) {
            $missingInfo = sprintf('Token do cartão: %s', var_export($creditCardToken, true));
            $missingInfo .= sprintf('/ Sender_hash: %s', var_export($senderHash, true));
            $missingInfo .= '/ URL desta requisição: ' . $pathRequest;
            $helper->writeLog(
                "Falha ao obter o token do cartao ou sender_hash.
                Ative o modo debug e observe o console de erros do seu navegador.
                Se esta for uma atualização via Ajax, ignore esta mensagem até a finalização do pedido, ou configure
                a url de exceção.
                $missingInfo"
            );
        }

        return $this;
    }


    /**
     * Validate data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     *
     * @throws Mage_Core_Exception
     */
    public function validateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        //what was validatable was validated in order to display welight as a payment method (isAvailable)
        //nothing more to be validate here. :O
        return $this;
    }

    /**
     * Submit to the gateway
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @param Mage_Payment_Model_Info              $paymentInfo
     */
    public function submitRecurringProfile(
        Mage_Payment_Model_Recurring_Profile $profile,
        Mage_Payment_Model_Info $paymentInfo
    ) {
        $welightPlanCode = $this->createwelightPlan($profile);

        $profile->setToken($welightPlanCode);
        $subResp = $this->subscribeToPlan($welightPlanCode, $paymentInfo, $profile);

        if (!isset($subResp->code) || empty($subResp->code)) {
            Mage::throwException('Falha ao realizar subscrição. Por favor tente novamente.');
        }

        $profile->setReferenceId((string)$subResp->code);
        $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_PENDING);

        $additionalInfo = $profile->getAdditionalInfo();
        $profile->setAdditionalInfo(
            array_merge(
                $additionalInfo, array('isSandbox' => Mage::helper('Welight_Gateway')->isSandbox())
            )
        );

        //método chamado em segundo lugar durante o processo de compra, depois do validate profile
    }

    /**
     * Fetch details
     *
     * @param string $referenceId
     * @param Varien_Object $result
     */
    public function getRecurringProfileDetails($referenceId, Varien_Object $result)
    {
        $profile = Mage::registry('current_recurring_profile');

        $subscriptionDetails = Mage::getModel('Welight_Gateway/recurring')->getPreApprovalDetails(
            $referenceId, $profile->getAdditionalInfo('isSandbox')
        );
        if (!isset($subscriptionDetails->status)) {
            return false;
        }

        switch ((string)$subscriptionDetails->status) {
            case 'ACTIVE':
                $result->setIsProfileActive(true);
                break;
            case 'INITIATED':
            case 'PENDING':
                $result->setIsProfilePending(true);
                break;
            case 'CANCELLED':
            case 'CANCELLED_BY_RECEIVER':
            case 'CANCELLED_BY_SENDER':
                $result->setIsProfileCanceled(true);
                break;
            case 'EXPIRED':
                $result->setIsProfileExpired(true);
                break;
            case 'SUSPENDED':
                $result->setIsProfileSuspended(true);
                break;
        }

        if ($profile->getId()) {
           $currentInfo = $profile->getAdditionalInfo();
           $currentInfo = is_array($currentInfo) ? $currentInfo : array();
            $profile->setAdditionalInfo(
                array_merge(
                    $currentInfo,
                    array('tracker'   => (string)$subscriptionDetails->tracker,
                        'reference' => (string)$subscriptionDetails->reference,
                        'status'    => (string)$subscriptionDetails->status)
                )
            );
            $profile->save();
            Mage::getModel('Welight_Gateway/recurring')->createOrders($profile);
        }

        $result->setAdditionalInformation(array('tracker' =>(string)$subscriptionDetails->tracker));

        //este método é chamado quando forçamos a atualização de um perfil no admin e via cron
    }

    /**
     * Check whether can get recurring profile details
     *
     * @return bool
     */
    public function canGetRecurringProfileDetails()
    {
        return true;
        //chamado quando entramos no perfil recorrente em Vendas > Perfil recorrente > clicamos em um perfil
        // TODO: Implement canGetRecurringProfileDetails() method.
    }

    /**
     * Update data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        //quando um perfil suspenso é reativado
        $a = 1;
        // TODO: Implement updateRecurringProfile() method.
    }

    /**
     * Manage status
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfileStatus(Mage_Payment_Model_Recurring_Profile $profile)
    {
        switch ($profile->getNewState()) {
            case Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED:
                $this->changewelightStatus($profile, 'SUSPENDED');
                break;
            case Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE:
                $this->changewelightStatus($profile, 'ACTIVE');
                break;
            case Mage_Sales_Model_Recurring_Profile::STATE_CANCELED:
                $this->cancelwelightProfile($profile);
                break;
        }
        
        $this->getRecurringProfileDetails($profile->getReferenceId(), new Varien_Object());

        //método chamado quando clicamos em Suspender, Ativar ou Cancelar um perfil
    }

    /**
     * Create welight plan and return plan code in welight
     * @param $profile
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    public function createwelightPlan($profile)
    {
        $helper = Mage::helper('Welight_Gateway/recurring');
        $currentInfo = $profile->getAdditionalInfo();
        $currentInfo = (!is_array($currentInfo)) ? array() : $currentInfo;
        $uniqIdRef = substr(strtoupper(uniqid()), 0, 7); //reference that will be used in product name and subscription
        $profile->setAdditionalInfo(array_merge($currentInfo, array('reference'=>$uniqIdRef)));
        $params = $helper->getCreatePlanParams($profile);
        $helper->writeLog('Criando plano de assinatura junto ao welight: ' . $params['preApprovalName']);
        $returnXml = $this->callApi($params, null, 'pre-approvals/request');

        $this->validateCreatePlanResponse($returnXml);

        $profile->setReferenceId($params['reference']);
        $this->mergeAdditionalInfo(
            array('recurringReference'         => $params['reference'],
                  'recurringwelightPlanCode' => (string)$returnXml->code,
                  'isSandbox'                 => Mage::helper('Welight_Gateway')->isSandbox(),
            )
        );

        $this->setPlanCode((string)$returnXml->code);

        return (string)$returnXml->code;
    }

    /**
     * @param string                               $welightPlanCode
     * @param Mage_Payment_Model_Info              $paymentInfo
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function subscribeToPlan($welightPlanCode, $paymentInfo, $profile)
    {
        $reference = $profile->getAdditionalInfo('reference');
        $profile->setReferenceId($reference);
        $profileInfo = $profile->getAdditionalInfo();
        $profileInfo = !is_array($profileInfo) ? array() : $profileInfo;
        $profile->setAdditionalInfo(array_merge($profileInfo, array('welightPlanCode'=>$welightPlanCode)));
        $jsonArray = array(
            'plan' => $welightPlanCode,
            'reference' => $reference,
            'sender' => Mage::helper('Welight_Gateway/params')->getSenderParamsJson($paymentInfo->getQuote()),
            'paymentMethod' => Mage::helper('Welight_Gateway/params')->getPaymentParamsJson($paymentInfo),
        );

        $body = Zend_Json::encode($jsonArray);

        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/vnd.welight.com.br.v1+json;charset=ISO-8859-1';
        Mage::helper('Welight_Gateway/recurring')->writeLog('Aderindo cliente ao plano');
        $response = $this->callJsonApi($body, $headers, 'pre-approvals', true);
        $this->validateJsonResponse($response);
        return $response;
    }

    /**
     * @param SimpleXMLElement $returnXml
     * @param array            $errMsg
     *
     * @throws Mage_Core_Exception
     */
    protected function validateCreatePlanResponse(SimpleXMLElement $returnXml)
    {
        $errMsg = array();
        $rmHelper = Mage::helper('Welight_Gateway');
        if (isset($returnXml->errors)) {
            foreach ($returnXml->errors as $error) {
                $errMsg[] = $rmHelper->__((string)$error->message) . ' (' . $error->code . ')';
            }

            Mage::throwException(
                'Um ou mais erros ocorreram ao criar seu plano de pagamento junto ao welight.' . PHP_EOL . implode(
                    PHP_EOL, $errMsg
                )
            );
        }

        if (isset($returnXml->error)) {
            $error = $returnXml->error;
            $errMsg[] = $rmHelper->__((string)$error->message) . ' (' . $error->code . ')';

            if (count($returnXml->error) > 1) {
                unset($errMsg);
                foreach ($returnXml->error as $error) {
                    $errMsg[] = $rmHelper->__((string)$error->message) . ' (' . $error->code . ')';
                }
            }

            Mage::throwException(
                'Um erro ocorreu ao criar seu plano de pagamento junto ao welight.' . PHP_EOL . implode(
                    PHP_EOL, $errMsg
                )
            );
        }

        if (!isset($returnXml->code)) {
            Mage::throwException(
                'Um erro ocorreu ao tentar criar seu plano de pagamento junto ao Pagseugro. O código do plano'
                . ' não foi retornado.'
            );
        }
    }


}