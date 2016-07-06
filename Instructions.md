###Installation Instructions:

1. Copy the "Pickrr_Magento1.xml" file to <magento_root>/app/etc/modules
2. Copy the Pickrr folder to <magento_root>/app/code/local
3. Verify in Magento Admin Panel whether the module is enabled. To check go to Admin>System>Configuration>Advanced>Advanced>Pickrr_magento1

---

###Usage Instructions:

####Import helper class:

```php
//import helper class

$helper = Mage::helper('pickrr_magento1/ExportShipment');

```

####Create a simple Pickrr Shipment:

**Prototype of the function:**
```php
createShipment($auth_token, $item_name, $pickup_time, $from_name, $from_phone_number, $from_pincode, $from_address, $to_name, $to_phone_number, $to_pincode, $to_address, $order_id = 'NULL', $cod=0.0);
```

It returns the tracking_id from Pickrr.

**Usage:**
```php
//Create shipment using order

$auth_key =  'Your Auth Key';

$helper->createOrderShipment($auth_key, "Item's Name", '2016-06-17 17:00', "Merchant/Sender's Name", "Merchant/Sender's Phone", 'Pickup Address Pin', 'Pickup Address');
```

---

####Create Shipment using order:

This will also create shipment and associate it with the passed order. The client/customer's address, item's name and order's id will be extracted from order.

**Prototype of the function:**
```php
createOrderShipment($auth_token, $order, $pickup_time, $from_name, $from_phone_number, $from_pincode, $from_address, $cod=0.0);

```

**Usage:**
```php
//Create shipment using order

$auth_key =  'Your Auth Key';
$order = Mage::getModel('sales/order')->loadByIncrementId('100000094-2');

$helper->createOrderShipment($auth_key, $order, '2016-06-17 17:00', "Merchant/Sender's Name", "Merchant/Sender's Phone", 'Pickup Address Pin', 'Pickup Address');
```