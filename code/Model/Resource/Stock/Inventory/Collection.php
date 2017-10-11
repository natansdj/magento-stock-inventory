<?php

/**
 * @category    VTI
 * @package     VTI_StockInventory
 * @version     1.0.0
 *
 */
class VTI_StockInventory_Model_Resource_Stock_Inventory_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Additional collection flags
     *
     * @var array
     */
    protected $_flags = array('isLists' => false);

    public function _construct()
    {
        $this->_init('vti_stockinventory/stock_inventory');
    }

    public function joinProduct()
    {
        //Product Name
        $pn_attributeCode = Mage::getModel('eav/entity')
            ->setType('catalog_product')
            ->getTypeId();
        $pn_attribute = Mage::getModel('eav/entity_attribute')
            ->loadByCode($pn_attributeCode, 'name')
            ->getAttributeId();
        $pn_table = Mage::getModel('core/resource')
            ->getTableName('catalog_product_entity_varchar');

        //VesBrand
        $vb_attributeCode = 'vesbrand';
        $vb_attribute = Mage::getModel("eav/entity_attribute")
            ->loadByCode('catalog_product', $vb_attributeCode);
        $vb_attributeId = $vb_attribute->getId();
        $vb_valueTable = $vb_attributeCode . '_t1';

        $vb_brand_table = Mage::getSingleton('core/resource')->getTableName('ves_brand/brand');
        $vb_tablePkName = 'brand_id';
        $vb_optionTable = $vb_attributeCode . '_option_value_t1';

        $store_id = '0';

        //Perform Join
        $this->getSelect()
            ->joinLeft(
                array('stock_item' => $this->getTable('cataloginventory/stock_item')),
                'main_table.item_id = stock_item.item_id',
                'product_id'
            )
            ->joinLeft(
                array('product' => $this->getTable('catalog/product')),
                'stock_item.product_id = product.entity_id',
                array('sku' => 'product.sku')
            )
            ->joinLeft(
                array('cpev' => $pn_table),
                "stock_item.product_id = cpev.entity_id"
                . " AND cpev.attribute_id={$pn_attribute}"
                . " AND cpev.store_id={$store_id}",
                array('name' => 'cpev.value')
            )
            ->joinLeft(
                array($vb_valueTable => $vb_attribute->getBackend()->getTable()),
                "stock_item.product_id={$vb_valueTable}.entity_id"
                . " AND {$vb_valueTable}.attribute_id='{$vb_attributeId}'"
                . " AND {$vb_valueTable}.store_id={$store_id}",
                array('vesbrand' => "{$vb_valueTable}.value")
            )
            ->joinLeft(
                array($vb_optionTable => $vb_brand_table),
                "{$vb_optionTable}.{$vb_tablePkName}={$vb_valueTable}.value",
                array('vesbrand_title' => "{$vb_optionTable}.title", 'brand_id')
            );

        return $this;
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();

        $prevItem = null;
        foreach ($this->getItems() as $item) {
            if ($item->hasOrigQty()) {
                $move = $item->getQty() - $item->getOrigQty();
                if ($move > 0) {
                    $move = '+' . $move;
                }
                $item->setMovement($move);
            }
        }

        return $this;
    }
}