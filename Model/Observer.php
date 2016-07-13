<?php
 
class Pickrr_Magento1_Model_Observer
{
    public function execute(Varien_Event_Observer $observer)
    {
        try{

            if ("0" == Mage::getStoreConfig('pickrr_magento1/general/automatic_shipment_enable'))
              return NULL;

            $order = $observer->getEvent()->getOrder();
            $payment = $order->getPayment();

            if($payment->getMethod() == "cashondelivery")
                $cod_amount = $order->getGrandTotal();
            else
                $cod_amount = 0.0;

            if ($order->getState() != "new" && $order->getState() != "pending_payment" )
               return NULL;

            $auth_token = Mage::getStoreConfig('pickrr_magento1/general/auth_token');

            $pickup_time = Mage::getStoreConfig('pickrr_magento1/shipment_details/pickup_time');
            $from_name = Mage::getStoreConfig('pickrr_magento1/shipment_details/from_name');
            $from_phone_number = Mage::getStoreConfig('pickrr_magento1/shipment_details/from_phone_number');
            $from_pincode = Mage::getStoreConfig('pickrr_magento1/shipment_details/from_pincode');
            $from_address = Mage::getStoreConfig('pickrr_magento1/shipment_details/from_address');

            $helper = Mage::helper('pickrr_magento1');

            $helper->createOrderShipment($auth_token, $order, $from_name, $from_phone_number, $from_pincode, $from_address, $pickup_time, $cod_amount);
        }
        catch (\Exception $e) {
            Mage::throwException($e->getMessage());
        }
    }
}