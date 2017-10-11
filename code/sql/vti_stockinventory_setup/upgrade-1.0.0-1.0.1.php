<?php
/**
 * @category    VTI
 * @package     VTI_StockInventory
 * @version     1.0.1
 *
 */

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$tableInventoryLog = $installer->getTable('vti_stock_inventory');

$installer->run("
    ALTER TABLE `{$tableInventoryLog}`
        ADD COLUMN `orig_qty` DECIMAL( 12, 4 ) NOT NULL default '0' AFTER `qty`;
");

$installer->endSetup();