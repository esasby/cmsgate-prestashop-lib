<?php
/**
 * Created by PhpStorm.
 * User: nikit
 * Date: 14.03.2018
 * Time: 17:08
 */

namespace esas\cmsgate\wrappers;

use Bitrix\Sale\BasketItem;
use esas\hutkigrosh\lang\TranslatorBitrix;

class OrderProductWrapperPrestashop extends OrderProductSafeWrapper
{
    private $productArray;

    /**
     * OrderProductWrapperJoomshopping constructor.
     * @param $product
     */
    public function __construct($productArray)
    {
        parent::__construct();
        $this->productArray = $productArray;
    }

    /**
     * Артикул товара
     * @return string
     */
    public function getInvIdUnsafe()
    {
        return $this->productArray['product_reference']; // может надо брать из поля product_id
    }

    /**
     * Название или краткое описание товара
     * @return string
     */
    public function getNameUnsafe()
    {
        return $this->productArray['product_name'];
    }

    /**
     * Количество товароа в корзине
     * @return mixed
     */
    public function getCountUnsafe()
    {
        return round($this->productArray['product_quantity']);
    }

    /**
     * Цена за единицу товара
     * @return mixed
     */
    public function getUnitPriceUnsafe()
    {
        return $this->productArray['unit_price_tax_incl'];
    }
}