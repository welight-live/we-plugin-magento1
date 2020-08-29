<?php
/**
 * PagSeguro Transparente Magento
 * Notification Controller responsible for receive order update notifications from PagSeguro
 * See how to setup notification url on module's official website
 *
 * @category    RicardoMartins
 * @package     Welight_Gateway
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/PagSeguro-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class Welight_Gateway_NotificationController extends Mage_Core_Controller_Front_Action
{
    /**
     * Receive and process pagseguro notifications.
     * Don' forget to setup your notification url as http://yourstore.com/index.php/pagseguro/notification
     */
    public function indexAction()
    {
        $helper = Mage::helper('welight_gateway');
        if ($helper->isSandbox()) {
            $this->getResponse()->setHeader('access-control-allow-origin', 'https://sandbox.pagseguro.uol.com.br');
        }

        if ($this->getRequest()->getPost('notificationCode', false) == false) {
            $this->getResponse()->setHttpResponseCode(422);
            $this->loadLayout();
            $this->renderLayout();
            return;
        }

        $notificationCode = $this->getRequest()->getPost('notificationCode');

        //Workaround for duplicated PagSeguro notifications (Issue #215)
        $exists = Mage::app()->getCache()->load($notificationCode);
        if ($exists) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody('Notificação já enviada a menos de 1 minuto.');
            return;
        }

        Mage::app()->getCache()->save('in_progress', $notificationCode, array('pagseguro_notification'), 60);

        /** @var Welight_Gateway_Model_Abstract $model */
        Mage::helper('welight_gateway')
            ->writeLog(
                'Notificação recebida do pagseguro com os parâmetros:'
                . var_export($this->getRequest()->getParams(), true)
            );
        $model =  Mage::getModel('welight_gateway/abstract');
        $response = $model->getNotificationStatus($notificationCode);
        if (false === $response) {
            Mage::throwException('Falha ao processar retorno XML do PagSeguro.');
        }

        $processedResult = $model->proccessNotificatonResult($response);
        if (false === $processedResult) {
            $this->getResponse()->setBody(
                'Falha ao processar notificação do PagSeguro. Consulte os logs para mais detalhes.'
            );
            $this->getResponse()->setHttpResponseCode(500);
            return;
        }

        if (isset($response->reference)) {
            $this->getResponse()->setBody('Notificação recebida para o pedido ' . (string)$response->reference);
        }

    }
}