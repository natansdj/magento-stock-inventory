<?php

/**
 * @category    VTI
 * @package     VTI_StockInventory
 * @version     1.0.0
 *
 */
class VTI_StockInventory_Block_Adminhtml_Stock_Inventory_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('StockInventoryGrid');
        $this->setSaveParametersInSession(true);
        $this->setFilterVisibility(!$this->getProduct());
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
    }

    /**
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::registry('current_product');
    }

    public function decorateSku($value, $row)
    {
        $html = sprintf(
            '<a href="%s" title="%s">%s</a>',
            $this->getUrl('adminhtml/catalog_product/edit', array('id' => $row->getProductId())),
            Mage::helper('vti_stockinventory')->__('View/Edit Product'),
            $value
        );

        return $html;
    }

    public function decorateUser($value, $row)
    {
        if (!$value) return '-';

        if ($row->getIsAdmin()) return $value;

        $html = sprintf(
            '<a href="%s" title="%s">%s</a>',
            $this->getUrl('adminhtml/customer/edit', array('id' => $row->getUserId())),
            Mage::helper('vti_stockinventory')->__('View/Edit User'),
            $value
        );

        return $html;
    }

    /**
     * @param $value
     * @param $row
     * @return string
     */
    public function decorateMessage($value, $row)
    {
        //check if message contains orderId
        if (strpos($value, 'order') !== false) {
            $orderId = Mage::helper('vti_stockinventory')->getOrderIdFromMessage($value);

            //show order link if order exists
            if (isset($orderId) && $orderId) {
                $html = sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    $this->getUrl('adminhtml/sales_order/view', array('order_id' => $orderId)),
                    Mage::helper('vti_stockinventory')->__('View/Edit Order'),
                    $value
                );
                return $html;
            }
        }

        return $value;
    }

    protected function _prepareCollection()
    {
        /** @var VTI_StockInventory_Model_Resource_Stock_Inventory_Collection $collection */
        $collection = Mage::getModel('vti_stockinventory/stock_inventory')->getCollection();

        if ($this->getProduct()) {
            /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
            $stockItem = Mage::getModel('cataloginventory/stock_item')
                ->loadByProduct($this->getProduct()->getId());
            if ($stockItem->getId()) {
                $collection->addFieldToFilter('item_id', $stockItem->getId());
            }
        } else {
            $collection->joinProduct();
            $collection->setFlag('isLists', true);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return Mage_Core_Model_Store
     */
    protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    protected function _prepareColumns()
    {
        /** @var VTI_StockInventory_Helper_Data $vtiStockInventoryHelper */
        $vtiStockInventoryHelper = Mage::helper('vti_stockinventory');

        //vesbrand_table
        $vb_attributeCode = 'vesbrand';
        $vb_table = $vb_attributeCode . '_t1';

        if (!$this->getProduct()) {
            $this->addColumn('sku', array(
                'header' => $vtiStockInventoryHelper->__('SKU'),
                'index' => 'sku',
                'filter_index' => 'product.sku',
                'type' => 'text',
                'width' => '100px',
                'frame_callback' => array($this, 'decorateSku'),
            ));
            $this->addColumn('product_name', array(
                'header' => $vtiStockInventoryHelper->__('Product Name'),
                'index' => 'name',
                'filter_index' => 'cpev.value',
                'type' => 'text',
            ));
            $this->addColumn('vesbrand', array(
                'header' => $vtiStockInventoryHelper->__('Brand'),
                'index' => 'vesbrand',
                'filter_index' => "{$vb_table}.value",
                'width' => '100px',
                'type' => 'options',
                'options' => $vtiStockInventoryHelper->listAllBrand(),
            ));
        }

        $this->addColumn('qty', array(
            'header' => $vtiStockInventoryHelper->__('Quantity'),
            'align' => 'right',
            'index' => 'qty',
            'type' => 'number',
            'width' => '80px',
            'filter_index' => 'main_table.qty',
        ));

        $this->addColumn('movement', array(
            'header' => $vtiStockInventoryHelper->__('Changes'),
            'align' => 'right',
            'index' => 'movement',
            'width' => '80px',
            'filter' => false,
        ));

        $this->addColumn('is_in_stock', array(
            'header' => $vtiStockInventoryHelper->__('In Stock'),
            'align' => 'right',
            'index' => 'is_in_stock',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'width' => '80px',
            'filter_index' => 'main_table.is_in_stock',
        ));

        $this->addColumn('message', array(
            'header' => $vtiStockInventoryHelper->__('Message'),
            'align' => 'left',
            'index' => 'message',
            'frame_callback' => array($this, 'decorateMessage'),
        ));

        $this->addColumn('user', array(
            'header' => $vtiStockInventoryHelper->__('User'),
            'align' => 'center',
            'index' => 'user',
            'width' => '100px',
            'frame_callback' => array($this, 'decorateUser'),
        ));

        $this->addColumn('created_at', array(
            'header' => $vtiStockInventoryHelper->__('Date'),
            'align' => 'right',
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '180px',
            'filter_index' => 'main_table.created_at',
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        //only on Stock Inventory Logs
        if (!$this->getProduct()) {
            $this->setMassactionIdField('id');
            $this->getMassactionBlock()->setFormFieldName('inventory_id');

            $this->getMassactionBlock()->addItem('delete', array(
                'label' => Mage::helper('vti_stockinventory')->__('Delete'),
                'url' => $this->getUrl('*/*/massDelete'),
                'confirm' => Mage::helper('vti_stockinventory')->__('Are you sure?')
            ));
        }
        return $this;
    }
}