<?php

/**
 * VTI StockInventory Model
 *
 * @method int getInventoryId()
 * @method int getItemId()
 * @method string getUser()
 * @method int getUserId()
 * @method bool getIsAdmin()
 * @method float getQty()
 * @method bool getIsInStock()
 * @method string getMessage()
 * @method string getCreatedAt()
 *
 * @category    VTI
 * @package     VTI_StockInventory
 * @version     1.0.0
 *
 */
class VTI_StockInventory_Model_Stock_Inventory extends Mage_Core_Model_Abstract
{
    const ENTITY = 'stock_inventory';

    protected $_eventObject = 'movement';

    protected function _construct()
    {
        $this->_init('vti_stockinventory/stock_inventory');
    }
}