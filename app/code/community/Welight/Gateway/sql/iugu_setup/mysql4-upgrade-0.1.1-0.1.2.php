<?php
/**
 * @category    Inovarti
 * @package     Welight_Gateway
 * @copyright   Copyright (c) 2014 Inovarti. (http://www.inovarti.com.br)
 */

/* @var $installer Mage_Sales_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->addAttribute('order_payment', 'iugu_total_with_interest', array('type' => Varien_Db_Ddl_Table::TYPE_DECIMAL));

$this->endSetup();
