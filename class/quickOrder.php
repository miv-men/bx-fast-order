<?
namespace Malashko;

use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\BasketTable;
use Bitrix\Sale\Order;
use Bitrix\Sale;

\Bitrix\Main\Loader::includeModule("catalog");
\Bitrix\Main\Loader::includeModule("sale");

/**
* Класс для оформления быстрого заказа
*/
class quickOrder
{

    protected $site = SITE_ID;

    public function user($user){

        $this->user = $user;

    }

    public function customRow($row){

        $this->custom = $row;

    }

    public function createOrder($id_product = false, $quantity = false){

        if($id_product)
            $basket = $this->productCart($id_product, $quantity);
        else
            $basket = $this->currentCart();

        if ($basket->getPrice() > 0)
            $this->addOrder($basket);
        else
            return false;

    }

    private function productCart($ids, $quantity){

        $basket = \Bitrix\Sale\Basket::create(SITE_ID);
        $basket = $basket->getOrderableItems();

        foreach ($ids as $k => $id) {

            $q = (empty($quantity[$k])) ? 1 : $quantity[$k];
//            foreach ($basket as $item) {
//                $item->getItemById($id)->delete();
//            }

            $item = $basket->createItem("catalog", $id);
            $item->setFields(array(
                'PRODUCT_ID' => $id,
                'CURRENCY' => 'RUB',
                'QUANTITY' => $q,
                'LID' => SITE_ID,
                'PRODUCT_PROVIDER_CLASS' => '\CCatalogProductProvider'
            ));
        }

        return $basket;

    }

    private function currentCart(){

        return Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());

    }

    private function _user(){

        global $USER;
        if (!empty($this->user['ID'])){
            $user['ID'] = $this->user['ID'];
            $user['EMAIL'] = '';

        } elseif ($USER->IsAuthorized()){
            $user['ID'] = $USER->GetID();
            $user['EMAIL'] = $USER->GetEmail();

        }else{
            $user['ID'] = Sale\Fuser::getId();
            $user['EMAIL'] = '';
        }
        return $user;

    }

    private function propertyCollection($order){

        if (!empty($this->user)){

            $i = 1;
            $collection = $order->getPropertyCollection();
            foreach ($this->user as $key => $value){

                if ($key == 'NAME')
                    $prop = $collection->getProfileName();
                elseif ($key == 'PHONE')
                    $prop = $collection->getPhone();
                elseif ($key == 'EMAIL')
                    $prop = $collection->getUserEmail();
                elseif ($key == 'ADDRESS')
                    $prop = $collection->getAddress();
                elseif ($key != 'ID'){
                    $order->setField($key, $value);
                    continue;
                }


                if ($prop) {
                    $prop->setValue($value);
                } else {
                    $prop = $collection->createItem([
                        'ID' => $i,
                        'NAME' => $key,
                        'TYPE' => 'STRING',
                        'CODE' => $key,
                    ]);
                    $prop->setField('VALUE', $value);
                    $i++;
                }

            }

        }

        if (!empty($this->custom)){

            $propertyCollection = $order->getPropertyCollection();
            foreach ($this->custom as $key => $value){

                foreach ($propertyCollection as $property) {
                    if ($property->getField('CODE') == $key) $property->setValue($value);
                }

            }

        }



    }

    private function paymentCollection($order){

        $paymentCollection = $order->getPaymentCollection();
        $payment = $paymentCollection->createItem(
            \Bitrix\Sale\PaySystem\Manager::getObjectById(1)
        );
        $payment->setField("SUM", $order->getPrice());
        $payment->setField("CURRENCY", $order->getCurrency());

    }

    public function addOrder($basket){

        $order = \Bitrix\Sale\Order::create(SITE_ID, $this->_user()['ID']);
        $order->setPersonTypeId(1);
        $order->setBasket($basket);

        $shipmentCollection = $order->getShipmentCollection();
        $shipment = $shipmentCollection->createItem(
            \Bitrix\Sale\Delivery\Services\Manager::getObjectById(1)
        );

        $shipmentItemCollection = $shipment->getShipmentItemCollection();

        foreach ($basket as $basketItem)
        {
            $item = $shipmentItemCollection->createItem($basketItem);
            $item->setQuantity($basketItem->getQuantity());
        }

        $this->paymentCollection($order);

        $this->propertyCollection($order);

        $result = $order->save();

        if (!$result->isSuccess())
        {
            return $result->getErrors();
        }else{

            return '<p class="h3">Ваш заказ принят!</p><p>Спасибо за покупку.</p>';

        }

    }

}
