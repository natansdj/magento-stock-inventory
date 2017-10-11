<?php

/**
 * @category    VTI
 * @package     VTI_StockInventory
 * @version     1.0.0
 *
 */
class VTI_StockInventory_Model_CatalogInventory_Stock extends Mage_CatalogInventory_Model_Stock
{
    public function revertProductsSale($items)
    {
        parent::revertProductsSale($items);
        Mage::dispatchEvent('cataloginventory_stock_revert_products_sale', array('items' => $items));

        return $this;
    }
}