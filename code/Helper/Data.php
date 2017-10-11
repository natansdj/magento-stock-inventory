<?php

/**
 * @category    VTI
 * @package     VTI_StockInventory
 * @version     1.0.0
 *
 */
class VTI_StockInventory_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Get OrderId from stock inventory message data
     *
     * @param $message
     * @return mixed|null
     */
    public function getOrderIdFromMessage($message)
    {
        $orderId = null;

        if (empty($message)) return $orderId;

        preg_match_all('!\d+!', $message, $getMatches);
        if (isset($getMatches[0])
            && $getMatches[0]
            && is_array($getMatches[0])
        ) {
            $orderIncrementId = implode(' ', $getMatches[0]);

            if (!is_null($orderIncrementId)) {
                $order = $this->getOrderByIncrementId($orderIncrementId);
                if ($order) $orderId = $order->getId();
            }
        }

        return $orderId;
    }

    /**
     * Retrieve order model object
     *
     * @param null $incrementId
     * @return Mage_Sales_Model_Order|Mage_Core_Model_Abstract
     */
    public function getOrderByIncrementId($incrementId = null)
    {
        $order = Mage::getModel('sales/order');
        if (!is_null($incrementId)) {
            $order->load($incrementId, 'increment_id');
        }

        return $order;
    }

    public function listAllBrand()
    {
        /** @var Ves_Brand_Model_Mysql4_Brand_Collection $vesBrandColl */
        $vesBrandColl = Mage::getModel('ves_brand/brand')->getCollection();
        $vesbrand_options = array();
        foreach ($vesBrandColl as $obj) {
            $vesbrand_options[$obj->getData('brand_id')] = $obj->getData('title');
        }

        return $vesbrand_options;
    }
}