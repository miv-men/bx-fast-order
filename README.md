# Покупка в 1 клик для сайта на 1с-Битрикс
###Класс позваляет оформить быстрый заказ для корзины текущего пользователя или для конкретного товара по ID.

Подключение класса:
```php
$quickOrder = new \Malashko\quickOrder();
```

Передать параметны покупателя:
```php
$user = [
    'ID' => $new_user_id, // если указан ID, то заказ будет оформлен от данного пользователя;
    в противном случае заказ оформится на авторизованного пользователя;
    'NAME' => $orderList[$i][1],
    'ADDRESS' => $orderList[$i][2],
    'PHONE' => $orderList[$i][3],
    'USER_DESCRIPTION' => $orderList[$i][6]
];
$quickOrder->user($user);
```

Передаем дополнительные свойства заказа:
```php
$quickOrder->customRow(['COMMENT' => 'ИЗ ЧАТА: '.$orderList[$i][0], "DELIVERY_SERVICE" => $orderList[$i][4]]);
```

Создаем заказ:
```php
$quickOrder->createOrder([301, 302], [2, 3]); // если передать пустые порметры, 
то заказ создастся из корзины, если указать id товаров и кол-во, то оформятся эти товары
```
