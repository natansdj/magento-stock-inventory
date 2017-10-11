<?php

/**
 * @category    VTI
 * @package     VTI_StockInventory
 * @version     1.0.0
 *
 */
class VTI_StockInventory_Block_Adminhtml_Stock_Inventory extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'vti_stockinventory';
        $this->_controller = 'adminhtml_stock_inventory';
        $this->_headerText = Mage::helper('vti_stockinventory')->__('Stock Inventory Logs');
        $this->_removeButton('add');
    }

    public function getHeaderCssClass()
    {
        return '';
    }

    protected function _prepareLayout()
    {
        $this->setChild('grid', $this->getLayout()->createBlock(
            'vti_stockinventory/adminhtml_stock_inventory_grid',
            'stock_inventory.grid'
        ));

        return parent::_prepareLayout();
    }
}