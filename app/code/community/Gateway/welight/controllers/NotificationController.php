<?php
/**
 * welight Transparente Magento
 * Notification Controller responsible for receive order update notifications from welight
 * See how to setup notification url on module's official website
 *
 * @category    Gateway
 * @package     gatewaywelight
 * @author      Ricardo Martins
 * @copyright   Copyright (c) 2015 Ricardo Martins (http://r-martins.github.io/welight-Magento-Transparente/)
 * @license     https://opensource.org/licenses/MIT MIT License
 */
class gatewaywelight_NotificationController extends Mage_Core_Controller_Front_Action
{
    /**
     * Receive and process welight notifications.
     * Don' forget to setup your notification url as http://yourstore.com/index.php/welight/notification
     */
    public function indexAction()
    {
        $helper = Mage::helper('gatewaywelight');
        if ($helper->isSandbox()) {
            $this->getResponse()->setHeader('access-control-allow-origin', 'https://sandbox.welight.uol.com.br');
        }

        if ($this->getRequest()->getPost('notificationCode', false) == false) {
            $this->getResponse()->setHttpResponseCode(422);
            $this->loadLayout();
            $this->renderLayout();
            return;
        }

        $notificationCode = $this->getRequest()->getPost('notificationCode');

        //Workaround for duplicated welight notifications (Issue #215)
        $exists = Mage::app()->getCache()->load($notificationCode);
        if ($exists) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setBody('Notificação já enviada a menos de 1 minuto.');
            return;
        }

        Mage::app()->getCache()->save('in_progress', $notificationCode, array('welight_notification'), 60);

        /** @var gatewaywelight_Model_Abstract $model */
        Mage::helper('gatewaywelight')
            ->writeLog(
                'Notificação recebida do welight com os parâmetros:'
                . var_export($this->getRequest()->getParams(), true)
            );
        $model =  Mage::getModel('gatewaywelight/abstract');
        $response = $model->getNotificationStatus($notificationCode);
        if (false === $response) {
            Mage::throwException('Falha ao processar retorno XML do welight.');
        }

        $processedResult = $model->proccessNotificatonResult($response);
        if (false === $processedResult) {
            $this->getResponse()->setBody(
                'Falha ao processar notificação do welight. Consulte os logs para mais detalhes.'
            );
            $this->getResponse()->setHttpResponseCode(500);
            return;
        }

        if (isset($response->reference)) {
            $this->getResponse()->setBody('Notificação recebida para o pedido ' . (string)$response->reference);
        }

    }
}