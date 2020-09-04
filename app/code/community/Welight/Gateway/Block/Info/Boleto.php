<?php
/**
 *
 * @category   Inovarti
 * @package    Welight_Gateway
 * @author     Suporte <suporte@inovarti.com.br>
 */
class Welight_Gateway_Block_Info_Boleto extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('welight/info/boleto.phtml');
    }

    /**
     * @return string
     */
    public function getInvoiceId()
    {
        return $this->getInfo()->getIuguInvoiceId();
    }

   /**
     * @return string
     */
    public function getInvoiceUrl()
    {
        return $this->getInfo()->getIuguPdf();
    }
}
