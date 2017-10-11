<?php

/**
 * @category    VTI
 * @package     VTI_StockInventory
 * @version     1.0.0
 *
 */
class VTI_StockInventory_Model_Resource_Stock_Inventory extends Mage_Core_Model_Resource_Db_Abstract
{
    public function insertStockInventory($stockInventory)
    {
        $this->_getWriteAdapter()->insertMultiple($this->getMainTable(), $stockInventory);

        return $this;
    }

    public function getProductsIdBySku($skus)
    {
        $select = $this->getReadConnection()
            ->select()
            ->from($this->getTable('catalog/product'), array('entity_id'))
            ->where('sku IN (?)', (array)$skus);

        return $this->getReadConnection()->fetchCol($select);
    }

    protected function _construct()
    {
        $this->_init('vti_stockinventory/stock_inventory', 'inventory_id');
    }

    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if (!$object->getCreatedAt()) {
            $object->setCreatedAt($this->formatDate(time()));
        }

        return parent::_beforeSave($object);
    }
}