<?php

/**
 * @category    VTI
 * @package     VTI_StockInventory
 * @version     1.0.0
 *
 */
class VTI_StockInventory_Model_Stock_Observer
{
    public function addStockInventoryTab()
    {
        $layout = Mage::getSingleton('core/layout');
        /** @var Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs $block */
        $block = $layout->getBlock('product_tabs');
        if ($block && $block->getProduct() && $block->getProduct()->getTypeId() == 'simple') {
            $block->addTab('stock_inventory', array(
                'after' => 'inventory',
                'label' => Mage::helper('vti_stockinventory')->__('Stock Inventory Log'),
                'content' => $layout->createBlock('vti_stockinventory/adminhtml_stock_inventory_grid')->toHtml(),
            ));
        }
    }

    public function cancelOrderItem($observer)
    {
        $item = $observer->getEvent()->getItem();

        $children = $item->getChildrenItems();
        $qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();

        if ($item->getId() && ($productId = $item->getProductId()) && empty($children) && $qty) {
            Mage::getSingleton('cataloginventory/stock')->backItemQty($productId, $qty);
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($item->getProductId());
            $this->insertStockInventory($stockItem, sprintf(
                    'Product restocked after order cancellation (order: %s)',
                    $item->getOrder()->getIncrementId())
            );
        }

        return $this;
    }

    /**
     * @param Mage_CatalogInventory_Model_Stock_Item $stockItem
     * @param string $message
     * @param array|Wizard_MassStatus_Model_Sales_Order $baseOrder
     * @param OrganicInternet_SimpleConfigurableProducts_Catalog_Model_Product $baseProduct
     */
    public function insertStockInventory(Mage_CatalogInventory_Model_Stock_Item $stockItem, $message = '', $baseOrder = array(), $baseProduct = array())
    {
        if ($stockItem->getId()) {

            $userName = $this->_getUsername();
            $userId = $this->_getUserId();

            //if userId not found in session (if new user && not yet confirmed), get customer from baseOrder data
            if (!$userId && $baseOrder) {
                $userId = $baseOrder->getCustomerId();
                $userName = $baseOrder->getCustomerName();
            }

            $originalInventoryQty = $stockItem->getOrigData('qty');
            //if coming from product order
            if ($baseProduct && $baseProduct instanceof Mage_Catalog_Model_Product) {
                if ($baseProduct->getStockItem()) {
                    $originalInventoryQty = $baseProduct->getStockItem()->getQty();
                }
            }

            Mage::getModel('vti_stockinventory/stock_inventory')
                ->setItemId($stockItem->getId())
                ->setUser($userName)
                ->setUserId($userId)
                ->setIsAdmin((int)Mage::getSingleton('admin/session')->isLoggedIn())
                ->setQty($stockItem->getQty())
                ->setOrigQty($originalInventoryQty)
                ->setIsInStock((int)$stockItem->getIsInStock())
                ->setMessage($message)
                ->save();
            Mage::getModel('catalog/product')->load($stockItem->getProductId())->cleanCache();
        }
    }

    protected function _getUsername()
    {
        $username = '-';
        if (Mage::getSingleton('api/session')->isLoggedIn()) {
            $username = Mage::getSingleton('api/session')->getUser()->getUsername();
        } elseif (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $username = Mage::getSingleton('customer/session')->getCustomer()->getName();
        } elseif (Mage::getSingleton('admin/session')->isLoggedIn()) {
            $username = Mage::getSingleton('admin/session')->getUser()->getUsername();
        }

        return $username;
    }

    protected function _getUserId()
    {
        $userId = null;
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $userId = Mage::getSingleton('customer/session')->getCustomerId();
        } elseif (Mage::getSingleton('admin/session')->isLoggedIn()) {
            $userId = Mage::getSingleton('admin/session')->getUser()->getId();
        }

        return $userId;
    }

    public function catalogProductImportFinishBefore($observer)
    {
        $productIds = array();
        $adapter = $observer->getEvent()->getAdapter();
        /** @var VTI_StockInventory_Model_Resource_Stock_Inventory $resource */
        $resource = Mage::getResourceModel('vti_stockinventory/stock_inventory');

        if ($adapter instanceof Mage_Catalog_Model_Convert_Adapter_Product) {
            $productIds = $adapter->getAffectedEntityIds();
        } else {
            Mage_ImportExport_Model_Import::getDataSourceModel()->getIterator()->rewind();
            $skus = array();
            while ($bunch = $adapter->getNextBunch()) {
                foreach ($bunch as $rowData) {
                    if (null !== $rowData['sku']) {
                        $skus[] = $rowData['sku'];
                    }
                }
            }
            if (!empty($skus)) {
                $productIds = $resource->getProductsIdBySku($skus);
            }
        }

        if (!empty($productIds)) {
            $stock = Mage::getSingleton('cataloginventory/stock');
            $stocks = Mage::getResourceModel('cataloginventory/stock')->getProductsStock($stock, $productIds);
            $stockInventory = array();
            $datetime = Varien_Date::formatDate(time());
            foreach ($stocks as $stockData) {
                $stockInventory[] = array(
                    'item_id' => $stockData['item_id'],
                    'user' => $this->_getUsername(),
                    'user_id' => $this->_getUserId(),
                    'qty' => $stockData['qty'],
                    'is_in_stock' => (int)$stockData['is_in_stock'],
                    'message' => 'Product import',
                    'created_at' => $datetime,
                );
            }

            if (!empty($stockInventory)) {
                $resource->insertStockInventory($stockInventory);
            }
        }
    }

    public function checkoutAllSubmitAfter($observer)
    {
        if ($observer->getEvent()->hasOrders()) {
            $orders = $observer->getEvent()->getOrders();
        } else {
            $orders = array($observer->getEvent()->getOrder());
        }
        $stockItems = array();
        foreach ($orders as $order) {
            if ($order) {
                foreach ($order->getAllItems() as $orderItem) {
                    /** @var Mage_Sales_Model_Order_Item $orderItem */
                    $productType = $orderItem->getProductType();
                    if ($orderItem->getQtyOrdered()) {
                        if ($productType == 'simple' || $productType == 'grouped') {
                            $stockItem = Mage::getModel('cataloginventory/stock_item')
                                ->loadByProduct($orderItem->getProductId());
                            if (!isset($stockItems[$stockItem->getId()])) {
                                $stockItems[$stockItem->getId()] = array(
                                    'item' => $stockItem,
                                    'orders' => array($order->getIncrementId()),
                                    'base_order' => $order,
                                    'base_product' => $orderItem->getProduct()
                                );
                            } else {
                                $stockItems[$stockItem->getId()]['orders'][] = $order->getIncrementId();
                                $stockItems[$stockItem->getId()]['base_order'] = $order;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($stockItems)) {
            foreach ($stockItems as $data) {
                $this->insertStockInventory($data['item'], sprintf(
                    'Product ordered (order%s: %s)',
                    count($data['orders']) > 1 ? 's' : '',
                    implode(', ', $data['orders'])
                ),
                    $data['base_order'],
                    $data['base_product']
                );
            }
        }
    }

    public function saveStockItemAfter($observer)
    {
        $stockItem = $observer->getEvent()->getItem();
        if (!$stockItem->hasStockStatusChangedAutomaticallyFlag() && $stockItem->getOriginalInventoryQty() != $stockItem->getQty()) {
            if (!$message = $stockItem->getSaveMovementMessage()) {
                if (Mage::getSingleton('api/session')->getSessionId()) {
                    $message = 'Stock saved from Magento API';
                } else {
                    $message = 'Stock saved manually';
                }
            }
            $this->insertStockInventory($stockItem, $message);
        }
    }

    public function stockRevertProductsSale($observer)
    {
        $items = $observer->getEvent()->getItems();
        foreach ($items as $productId => $item) {
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
            if ($stockItem->getId()) {
                $message = 'Product restocked';
                if ($creditMemo = Mage::registry('current_creditmemo')) {
                    $message = sprintf(
                        'Product restocked after credit memo creation (credit memo: %s)',
                        $creditMemo->getIncrementId()
                    );
                }
                $this->insertStockInventory($stockItem, $message);
            }
        }
    }
}
