<?php
/**
 * Migra configurações antigas(se houver) pro padrão novo, evitando conflitos com outros módulos welight
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$token = Mage::getStoreConfig('payment/welight/token');
$decryptedToken = Mage::helper('core')->decrypt($token);

if ($token != false && (strlen($decryptedToken) == 32 || strlen($decryptedToken) == 100) ) {
    $sql = "UPDATE {$this->getTable('core/config_data')} 
            SET path = REPLACE(path, 'payment/welight/', 'payment/rm_welight/')
            WHERE path LIKE 'payment/welight/%'";

    $installer->getConnection()->query($sql);
}
