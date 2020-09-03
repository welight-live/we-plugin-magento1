<?php
/**
 *
 * @category   Inovarti
 * @package    Welight_Gateway
 * @author     Suporte <suporte@inovarti.com.br>
 */

class Welight_Gateway_Adminhtml_Iugu_InvoiceController extends Mage_Adminhtml_Controller_Action
{
    public function viewAction()
    {
        $id = $this->getRequest()->getParam('id');
        $result = array();
        $result['success'] = false;
        try {
            $invoice = Mage::getSingleton('iugu/api')->fetch($id);
            if ($invoice->getId()) {
                $result['content_html'] = $this->_getInvoiceHtml($invoice);
                $result['success'] = true;
            } else {
                Mage::throwException($invoice->getErrors());
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error_message'] = $e->getMessage();
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    protected function _getInvoiceHtml($invoice)
    {
        $this->loadLayout();
        $blockType = $invoice->getBankSlip() ? 'boleto' : 'cc';
        $block = $this->getLayout()->createBlock('iugu/adminhtml_invoice_view_'. $blockType);
        $block->setInvoice($invoice);
        return $block->toHtml();
    }
}
