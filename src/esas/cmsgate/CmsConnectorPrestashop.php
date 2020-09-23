<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 13.04.2020
 * Time: 12:23
 */

namespace esas\cmsgate;


use Context;
use esas\cmsgate\descriptors\CmsConnectorDescriptor;
use esas\cmsgate\descriptors\VendorDescriptor;
use esas\cmsgate\descriptors\VersionDescriptor;
use esas\cmsgate\lang\LocaleLoaderPrestashop;
use esas\cmsgate\wrappers\OrderWrapper;
use esas\cmsgate\wrappers\OrderWrapperPrestashop;
use Order;

class CmsConnectorPrestashop extends CmsConnector
{
    /**
     * Для удобства работы в IDE и подсветки синтаксиса.
     * @return $this
     */
    public static function getInstance()
    {
        return Registry::getRegistry()->getCmsConnector();
    }


    public function createCommonConfigForm($managedFields)
    {
        return null; //not implemented
    }

    public function createSystemSettingsWrapper()
    {
        return null; // not implemented
    }

    /**
     * По локальному id заказа возвращает wrapper
     * @param $orderId
     * @return OrderWrapper
     */
    public function createOrderWrapperByOrderId($orderId)
    {
        $prestashopOrder = new Order($orderId);
        return new OrderWrapperPrestashop($prestashopOrder);
    }

    public function createOrderWrapperForCurrentUser()
    {
        $prestashopOrder = Order::getByCartId(Context::getContext()->cart->id); // возможно стоит получать через Order::getCustomerOrders((int)$this->context->customer->id))
        return new OrderWrapperPrestashop($prestashopOrder);
    }

    public function createOrderWrapperByOrderNumber($orderNumber)
    {
        $prestashopOrderCollection = Order::getByReference($orderNumber);
        $prestashopOrderCollection->orderBy("date_add", "desc"); //todo check
        return new OrderWrapperPrestashop($prestashopOrderCollection->getFirst());
    }

    public function createOrderWrapperByExtId($extId)
    {
        //todo
    }

    public function createConfigStorage()
    {
        return new ConfigStoragePrestashop();
    }

    public function createLocaleLoader()
    {
        return new LocaleLoaderPrestashop();
    }

    public function createCmsConnectorDescriptor()
    {
        return new CmsConnectorDescriptor(
            "cmsgate-prestashop-lib",
            new VersionDescriptor(
                "v1.12.0",
                "2020-09-23"
            ),
            "Cmsgate Prestashop connector",
            "https://bitbucket.esas.by/projects/CG/repos/cmsgate-prestashop-lib/browse",
            VendorDescriptor::esas(),
            "prestashop"
        );
    }
}