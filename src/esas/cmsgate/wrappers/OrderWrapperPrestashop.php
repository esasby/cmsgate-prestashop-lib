<?php

namespace esas\cmsgate\wrappers;

use Address;
use Currency;
use Order;
use OrderCore;
use OrderHistory;

class OrderWrapperPrestashop extends OrderSafeWrapper
{
    protected $order;
    protected $products;
    /**
     * @var Address
     */
    protected $address;

    /**
     * OrderWrapperJoomshopping constructor.
     * @param $order
     */
    public function __construct(OrderCore $order)
    {
        parent::__construct();
        $this->order = $order;
        if ($order != null && !empty($this->order->id_address_delivery))
            $this->address = new Address($this->order->id_address_delivery);
    }

    /**
     * Уникальный номер заказ в рамках CMS
     * @return string
     */
    public function getOrderIdUnsafe()
    {
        return $this->order->id;
    }

    public function getOrderNumberUnsafe()
    {
        // если включен шаблон генерации номера заказа, то подставляем этот номер
        $reference = $this->order->reference;
        return !empty($reference) ? $reference : $this->getOrderId();
    }

    /**
     * Полное имя покупателя
     * @return string
     */
    public function getFullNameUnsafe()
    {
        return $this->order->getCustomer()->firstname . " " . $this->order->getCustomer()->lastname;
    }

    /**
     * Мобильный номер покупателя для sms-оповещения
     * (если включено администратором)
     * @return string
     */
    public function getMobilePhoneUnsafe()
    {

        return $this->address->phone_mobile;
    }

    /**
     * Email покупателя для email-оповещения
     * (если включено администратором)
     * @return string
     */
    public function getEmailUnsafe()
    {
        return $this->order->getCustomer()->email;
    }

    /**
     * Физический адрес покупателя
     * @return string
     */
    public function getAddressUnsafe()
    {
        if ($this->address != null)
            return implode(", ", [
                $this->address->country,
                $this->address->city,
                $this->address->address1,
                $this->address->address2
            ]);
        else
            return "";
    }

    /**
     * Общая сумма товаров в заказе
     * @return string
     */
    public function getAmountUnsafe()
    {
        return $this->order->getTotalPaid();
    }

    /**
     * Валюта заказа (буквенный код)
     * @return string
     */
    public function getCurrencyUnsafe()
    {
        $currency = new Currency($this->order->id_currency);
        return $currency->iso_code;
    }

    /**
     * Массив товаров в заказе
     * @return \esas\cmsgate\wrappers\OrderProductWrapper[]
     */
    public function getProductsUnsafe()
    {
        if ($this->products != null)
            return $this->products;
        foreach ($this->order->getProducts() as $productArray)
            $this->products[] = new OrderProductWrapperPrestashop($productArray);
        return $this->products;
    }

    /**
     * Текущий статус заказа в CMS
     * @return mixed
     */
    public function getStatusUnsafe()
    {
        return $this->order->current_state;
    }

    /**
     * Обновляет статус заказа в БД
     * @param $newStatus
     * @return mixed
     */
    public function updateStatus($newStatus)
    {
        if (!empty($newStatus) && $this->getStatus() != $newStatus) {
            $history = new OrderHistory();
            $history->id_order = $this->getOrderId();
            $history->changeIdOrderState($newStatus, $this->getOrderId()); //order status=3
        }
    }

    /**
     * Идентификатор клиента
     * @return string
     */
    public function getClientIdUnsafe()
    {
        return $this->order->id_customer;
    }

    /**
     * BillId (идентификатор хуткигрош) успешно выставленного счета
     * @return mixed
     */
    public function getExtIdUnsafe()
    {
        return ""; //todo
    }

    /**
     * Сохраняет привязку внешнего идентификтора к заказу
     * @param $extId
     */
    public function saveExtId($extId)
    {
        //todo
    }
}