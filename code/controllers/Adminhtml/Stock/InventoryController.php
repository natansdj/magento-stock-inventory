<?php

/**
 * @category    VTI
 * @package     VTI_StockInventory
 * @version     1.0.0
 *
 */
class VTI_StockInventory_Adminhtml_Stock_InventoryController extends Mage_Adminhtml_Controller_Action
{
    public function listAction()
    {
        $this->_title($this->__('Catalog'))
            ->_title(Mage::helper('vti_stockinventory')->__('Stock Inventory Logs'));
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('vti_stockinventory/adminhtml_stock_inventory'));
        $this->_setActiveMenu('catalog/stock_inventory');
        $this->renderLayout();
    }

    public function massDeleteAction()
    {
        $requestIds = $this->getRequest()->getParam('inventory_id');
        if (!is_array($requestIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select request(s)'));
        } else {
            try {
                foreach ($requestIds as $requestId) {
                    $RequestData = Mage::getModel('vti_stockinventory/stock_inventory')->load($requestId);
                    $RequestData->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($requestIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/list');
    }
}