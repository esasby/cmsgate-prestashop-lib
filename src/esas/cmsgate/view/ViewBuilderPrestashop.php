<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 28.02.2020
 * Time: 10:36
 */

namespace esas\cmsgate\view;

use esas\cmsgate\messenger\Messages;
use esas\cmsgate\Registry;
use esas\cmsgate\utils\htmlbuilder\Attributes as attribute;
use esas\cmsgate\utils\htmlbuilder\Elements as element;
use esas\cmsgate\utils\RequestParams;
use esas\cmsgate\view\admin\AdminViewFields;
use esas\cmsgate\wrappers\OrderWrapperOpencart;
use esas\cmsgate\wrappers\SystemSettingsWrapperOpencart;

class ViewBuilderPrestashop extends ViewBuilder
{
    public static function elementAdminMessages()
    {
        return
            parent::elementMessages(
                "alert alert-success",
                "alert alert-danger",
                "alert alert-danger"
            );

    }

    public static function elementClientMessages()
    {
        return
            parent::elementMessages(
                "alert alert-success",
                "alert alert-danger",
                "alert alert-danger"
            );

    }
    
    public static function elementMessage($class, $text)
    {
        return
            element::div(
                attribute::clazz($class),
                element::i(
                    attribute::clazz("fa fa-exclamation-circle")
                ),
                element::content($text),
                element::button(
                    attribute::type("button"),
                    attribute::clazz("close"),
                    attribute::data_dismiss("alert")
                )
            );
    }

    /**
     * @param OrderWrapperOpencart $orderWrapper
     * @return \esas\cmsgate\utils\htmlbuilder\Element
     * @throws \Throwable
     */
    public static function elementConfirmOrderForm($orderWrapper)
    {
        return
            element::form(
                attribute::action(SystemSettingsWrapperOpencart::getInstance()->linkCatalogExtension("pay")),
                attribute::method("post"),
                element::input(
                    attribute::type("hidden"),
                    attribute::name(RequestParams::ORDER_ID),
                    attribute::value($orderWrapper->getOrderId())
                ),
                element::div(
                    attribute::clazz("buttons"),
                    element::div(
                        attribute::clazz("pull-right"),
                        element::input(
                            attribute::type("submit"),
                            attribute::clazz("btn btn-primary"),
                            attribute::value(Registry::getRegistry()->getTranslator()->translate("Confirm"))
                        )
                    )
                )
            );
    }

    /**
     * Для добавления информационной надписи о режими "sandbox"
     * @return string
     */
    public static function elementSandboxMessage()
    {
        if (Registry::getRegistry()->getConfigWrapper()->isSandbox()) {
            return
                element::div(
                    attribute::clazz("alert alert-info"),
                    element::content(Registry::getRegistry()->getTranslator()->translate(Messages::SANDBOX_MODE_IS_ON))
                );
        } else
            return "";
    }

    /**
     * Для добавления кнопик "Продолжить" на последнем экране
     * @return string
     */
    public static function elementButtonContinue($link, $label)
    {
        return element::a(
            attribute::href($link),
            attribute::clazz("btn btn-primary"),
            element::content($label)
        );
    }

    /**
     * Для описания платежной системы на странице настроек
     * @return string
     */
    public static function elementPaymentMethodDescription()
    {
        return element::div(
            attribute::clazz("alert alert-info"),
            element::img(
                attribute::src(""),
                attribute::style("float:left; margin-right:15px;")
//                attribute::height()
            ),
            element::p(
                Registry::getRegistry()->getTranslator()->translate(AdminViewFields::ADMIN_PAYMENT_METHOD_DESCRIPTION)
            )
        );
    }
}