<?php

class Pickrr_Magento1_Helper_Data
extends Mage_Core_Helper_Abstract
{

    // Some code referred from: http://www.anyknowledge.com/magento-programmatically-add-shipment-with-its-tracking-number-to-any-specific-order/

    public function completeShipment($order, $shipmentTrackingNumber)
    {     
        if (!$order->getId()) {
            Mage::throwException("Order does not exist, for the Shipment process to complete");
        }
     
        if ($order->canShip()) {
            try {
                $shipment = Mage::getModel('sales/service_order', $order)
                                ->prepareShipment($this->_getItemQtys($order));

                $shipmentCarrierCode = 'custom';
                $shipmentCarrierTitle = 'Pickrr';
     
                $arrTracking = array(
                    'carrier_code' => isset($shipmentCarrierCode) ? $shipmentCarrierCode : $order->getShippingCarrier()->getCarrierCode(),
                    'title' => isset($shipmentCarrierTitle) ? $shipmentCarrierTitle : $order->getShippingCarrier()->getConfigData('title'),
                    'number' => $shipmentTrackingNumber,
                );
     
                $track = Mage::getModel('sales/order_shipment_track')->addData($arrTracking);
                $shipment->addTrack($track);
     
                $shipment->register();
     
                $this->_saveShipment($shipment, $order);
     
                $this->_saveOrder($order);
            } catch (Exception $e) {
                throw $e;
            }
        }
    }
     
    /**
     * Get the Quantities shipped for the Order, based on an item-level
     * This method can also be modified, to have the Partial Shipment functionality in place
     *
     * @param $order Mage_Sales_Model_Order
     * @return array
     */
    protected function _getItemQtys(Mage_Sales_Model_Order $order)
    {
        $qty = array();
     
        foreach ($order->getAllItems() as $_eachItem) {
            if ($_eachItem->getParentItemId()) {
                $qty[$_eachItem->getParentItemId()] = $_eachItem->getQtyOrdered();
            } else {
                $qty[$_eachItem->getId()] = $_eachItem->getQtyOrdered();
            }
        }
     
        return $qty;
    }
     
    /**
     * Saves the Shipment changes in the Order
     *
     * @param $shipment Mage_Sales_Model_Order_Shipment
     * @param $order Mage_Sales_Model_Order
     * @param $customerEmailComments string
     */
    protected function _saveShipment(Mage_Sales_Model_Order_Shipment $shipment, Mage_Sales_Model_Order $order, $customerEmailComments = NULL)
    {
        $shipment->getOrder()->setIsInProcess(true);
        $transactionSave = Mage::getModel('core/resource_transaction')
                               ->addObject($shipment)
                               ->addObject($order)
                               ->save();
     
        $emailSentStatus = $shipment->getData('email_sent');
        if (!is_null($customerEmailComments) && !$emailSentStatus) {
            $shipment->sendEmail(true, $customerEmailComments);
            $shipment->setEmailSent(true);
        }
    }
     
    /**
     * Saves the Order, to complete the full life-cycle of the Order
     * Order status will now show as Complete
     *
     * @param $order Mage_Sales_Model_Order
     */
    protected function _saveOrder(Mage_Sales_Model_Order $order)
    {
        $order->setData('state', Mage_Sales_Model_Order::STATE_PROCESSING);
        $order->setData('status', Mage_Sales_Model_Order::STATE_PROCESSING);
     
        $order->save();
    }

    public function createShipment($params)
    {
        try{
            $json_params = json_encode( $params );

            $url = 'http://www.pickrr.com/api/place-order/';
            //open connection
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $json_params);
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

            //execute post
            $result = curl_exec($ch);
            $result = json_decode($result, true);

            //close connection
            curl_close($ch);

            if(gettype($result)!="array")
              throw new \Exception( print_r($result, true) . "Problem in connecting with Pickrr");

            if($result['err']!="")
              throw new \Exception($result['err']);

            return $result['tracking_id'];

        }
        catch (\Exception $e) {
            Mage::throwException(__('There was an error in creating the Pickrr shipment: %1.', $e->getMessage()));
        }


    }

    public function createOrderShipment($order)
    {
        try{
            $itemCount = $order->getTotalItemCount();
            $item_name = "NULL";
            if($itemCount==1) $item_name = $order->getItemsCollection()->getFirstItem()->getName();
            else $item_name = 'Multiple Items';
            
            $shipping_address = $order->getShippingAddress();
            $client_order_id = $order->getIncrementId();
            $invoice_value = $order->getGrandTotal();
            
            $params = array(
                      'auth_token' => Mage::getStoreConfig('pickrr_magento1/general/auth_token'),
                      'client_order_id' => $client_order_id,
                      'invoice_value' => $invoice_value,
                      'item_name' => $item_name,
                      'from_name' => Mage::getStoreConfig('pickrr_magento1/shipment_details/from_name'),
                      'from_phone_number' => Mage::getStoreConfig('pickrr_magento1/shipment_details/from_phone_number'),
                      'from_pincode'=> Mage::getStoreConfig('pickrr_magento1/shipment_details/from_pincode'),
                      'from_address'=> Mage::getStoreConfig('pickrr_magento1/shipment_details/from_address'),
                      'to_name'=> $shipping_address->getName(),
                      'to_phone_number' => $shipping_address->getTelephone(),
                      'to_pincode' => $shipping_address->getPostcode(),
                      'to_address' => implode(', ', $shipping_address->getStreet()) . ", " . $shipping_address->getCity() . ", " . $shipping_address->getRegion()
                    );
            
            $payment = $order->getPayment();
            if($payment->getMethod() == "cashondelivery")
                $cod_amount = $order->getGrandTotal();
            else
                $cod_amount = 0.0;
            $pickup_time = Mage::getStoreConfig('pickrr_magento1/shipment_details/pickup_time');
            if($cod>0.0) $params['cod_amount'] = $cod;
            if($pickup_time!='NULL') $params['order_time'] = $pickup_time;

            $tracking_no = $this->createShipment($params);
            $this->completeShipment($order, $tracking_no);
        }
        catch (\Exception $e) {
            Mage::throwException(__('There was an error in creating a Pickrr shipment using order object: %1.', $e->getMessage()));
        }
      }


    public function export($shipment)
    {
        try{
            $order = $shipment->getOrder();
            $billing_address = $shipment->getBillingAddress();
            $shipping_address = $shipment->getShippingAddress();

            $params = array(
                  'store_id' => $shipment->getStoreId(),
                  'order_id' => $shipment->getOrderId(),
                  'customer_id' => $order->getCustomerId(),
                  'customer_name' => $order->getCustomerName(),
                  'customer_email' => $order->getCustomerEmail(),
                  'billing_address' => $billing_address->debug(),
                  'shipping_address' => $shipping_address->debug(),
                  'entity_id' => $shipment->getEntityId(),
                  'created_at' => $shipment->getCreatedAt(),
                  'tracks' => array()
            );

            $trackingNumbers = array();
            foreach ($shipment->getAllTracks() as $track) {
                array_push($params['tracks'],$track->debug());
            };
            // Mage::getStoreConfig('pickrr/api_key');
            
            $url = 'http://www.pickrr.com';
            $params = http_build_query($params);

            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POST, count($params));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $params);

            $result = curl_exec($ch);
        }
        catch (\Exception $e) {
            Mage::throwException(__('There was an error exporting the shipment to Pickrr: %1.', $e->getMessage()));
        }

        return 1;
    }
}
