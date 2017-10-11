<?php
/**
 * @category    VTI
 * @package     VTI_StockInventory
 * @version     1.0.0
 *
 */

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$tableInventoryLog = $installer->getTable('vti_stock_inventory');
$tableItem = $installer->getTable('cataloginventory_stock_item');

$installer->run("
DROP TABLE IF EXISTS {$tableInventoryLog};
CREATE TABLE {$tableInventoryLog} (
`inventory_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`item_id` INT( 10 ) UNSIGNED NOT NULL ,
`user` varchar(40) NOT NULL DEFAULT '',
`user_id` mediumint(9) unsigned DEFAULT NULL,
`is_admin` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
`qty` DECIMAL( 12, 4 ) NOT NULL default '0',
`is_in_stock` TINYINT( 1 ) UNSIGNED NOT NULL default '0',
`message` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`created_at` DATETIME NOT NULL ,
INDEX ( `item_id` )
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
");

$installer->getConnection()->addConstraint('FK_STOCK_INVENTORY_ITEM', $tableInventoryLog, 'item_id', $tableItem, 'item_id');

$installer->endSetup();
