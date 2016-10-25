<?php
 
class Pickrr_Magento1_Model_Observer
{
    public function execute(Varien_Event_Observer $observer)
    {
        try{
            if ("0" == Mage::getStoreConfig('pickrr_magento1/general/automatic_shipment_enable'))
              return NULL;

            $order = $observer->getEvent()->getOrder();
            if ($order->getState() != "new" && $order->getState() != "pending_payment" )
               return NULL;

            $helper = Mage::helper('pickrr_magento1');
            $helper->createOrderShipment($order);
        }
        catch (\Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }
}
