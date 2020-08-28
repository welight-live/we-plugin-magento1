<?php
class Welight_Gateway_Model_Observer
{
    /**
     * Adiciona o bloco do direct payment logo após um dos forms do welight ter sido inserido.
     * @param $observer
     *
     * @return $this
     */
    public function addDirectPaymentBlock($observer)
    {
        $welightBlocks = array(
            'Welight_Gatewaypro/form_tef',
            'Welight_Gatewaypro/form_boleto',
            'Welight_Gateway/form_cc',
            'Welight_Gateway/form_recurring',
        );
        $blockType = $observer->getBlock()->getType();
        if (in_array($blockType, $welightBlocks)) {
            $output = $observer->getTransport()->getHtml();
            $directpayment = Mage::app()->getLayout()
                                ->createBlock('Welight_Gateway/form_directpayment')
                                ->toHtml();
            $observer->getTransport()->setHtml($directpayment . $output);
        }

        return $this;
    }

    /**
     * Used to display notices and warnings regarding incompatibilities with the saved recurring product and welight
     * @param $observer
     */
    public static function validateRecurringProfile($observer)
    {
        $product = $observer->getProduct();
        if (!$product || !$product->isRecurring()) {
            return;
        }

        $helper = Mage::helper('Welight_Gateway/recurring');
        $profile = $product->getRecurringProfile();
        $welightPeriod = $helper->getwelightPeriod($profile);

        if (false === $welightPeriod) {
            Mage::getSingleton('core/session')->addWarning(
                'O welight não será exibido como meio de pagamento para este produto, pois as configurações do '
                . 'ciclo de cobrança não são suportadas. <a href="https://welighttransparente.zendesk.com/hc/pt'
                . '-br/articles/360044169531" target="_blank">Clique aqui</a> para saber mais.'
            );
        }

        if ($profile['start_date_is_editable']) {
            Mage::getSingleton('core/session')->addWarning(
                'O welight não será exibido como meio de pagamento para este produto, pois não é possível'
                . ' definir a Data de Início em planos com cobrança automática.'
            );
        }

        if ($profile['trial_period_unit']) {
            if (!$profile['trial_period_max_cycles']) {
                Mage::getSingleton('core/session')->addWarning(
                    'Periodo máximo de cobranças te'
                    . 'mporárias deve ser especificado. Este valor será ignorado quando usado no welight, '
                    . 'mas o Magento impedirá a finalização de um pedido.'
                );
            }

            if (!$profile['trial_billing_amount']) {
                Mage::getSingleton('core/session')->addWarning(
                    'Valor temporário de cobranças deve ser especificado. Este valor será ignorado quando usado'
                    . ' no welight, mas o Magento impedirá a finalização de um pedido.'
                );
            }
        }

    }

    /**
     * This will create a new customer and update customer_id in the recurring profile if checkout is METHOD_REGISTER
     * This solves a Magento bug in recurring profiles
     * @param $observer
     *
     * @throws Exception
     */
    public function updateRecurringCustomerId($observer)
    {
        if (!$observer->getObject() || $observer->getObject()->getResourceName() != 'sales/recurring_profile') {
            return;
        }

        $quote = $observer->getObject()->getQuote();
        if (!$quote || !$quote->getId()) {
            return;
        }

        if ($quote->getData('checkout_method') != Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER) {
            return;
        }

        #registers the customer (extracted from \Mage_Checkout_Model_Type_Onepage::_prepareNewCustomerQuote)
        $billing    = $quote->getBillingAddress();
        $shipping   = $quote->isVirtual() ? null : $quote->getShippingAddress();

        $customer = $quote->getCustomer();
        $customerBilling = $billing->exportCustomerAddress();
        $customer->addAddress($customerBilling);
        $billing->setCustomerAddress($customerBilling);
        $customerBilling->setIsDefaultBilling(true);
        if ($shipping && !$shipping->getSameAsBilling()) {
            $customerShipping = $shipping->exportCustomerAddress();
            $customer->addAddress($customerShipping);
            $shipping->setCustomerAddress($customerShipping);
            $customerShipping->setIsDefaultShipping(true);
        } else {
            $customerBilling->setIsDefaultShipping(true);
        }

        Mage::helper('core')->copyFieldset('checkout_onepage_quote', 'to_customer', $quote, $customer);
        $customer->setPassword($customer->getPassword());
        $passwordCreatedTime = Mage::getSingleton('checkout/session')
                                   ->getData('_session_validator_data')['session_expire_timestamp']
            - Mage::getSingleton('core/cookie')->getLifetime();
        $customer->setPasswordCreatedAt($passwordCreatedTime);
        $quote->setCustomer($customer)
            ->setCustomerId(true);
        $quote->setPasswordHash('');

        $customer->save();
        $customerId = $customer->getEntityId();
        $observer->getObject()->getQuote()->setCustomerId($customerId);
        $data = array('customer_id' => $customerId);
        $observer->getObject()->setOrderInfo(array_merge($observer->getObject()->getOrderInfo(), $data));
        $observer->getObject()->setCustomerId($customerId);
    }
}