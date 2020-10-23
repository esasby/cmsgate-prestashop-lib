<?php
namespace esas\cmsgate\prestashop;
use Cart;
use Customer;
use esas\cmsgate\CmsConnectorPrestashop;
use esas\cmsgate\Registry;
use Module;
use ModuleFrontController;
use Order;
use Tools;

class CmsgateModuleFrontController extends ModuleFrontController
{
    public function validateModule()
    {
        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName()) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'payment'));
        }
    }

    public function getOrderWrapper($orderNumber = null, $orderId = null) {
        $orderWrapper = null;
        if ($orderNumber != null)
            $orderWrapper = Registry::getRegistry()->getOrderWrapperByOrderNumber($orderNumber);
        elseif ($orderId != null)
            $orderWrapper = Registry::getRegistry()->getOrderWrapper($orderId);
        else {
            $cart = $this->context->cart;
            if ($cart->id == null || $cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
                Tools::redirect('index.php?controller=order&step=1');
            }
            if (Order::getByCartId($cart->id) == null)
                $this->createOrderByCart();
            $orderWrapper = Registry::getRegistry()->getOrderWrapperForCurrentUser();
        }
        return $orderWrapper;
    }

    /**
     * Принудительное создание заказа, т.к. в prestashop заказ создается только после платежа, до этого момента есть только корзина
     */
    public function createOrderByCart()
    {
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $mailVars = [];
        $this->module->validateOrder(
            (int)$cart->id,
            CmsConnectorPrestashop::getInstance()->getOrderInitialState(),
            $total,
            $this->module->displayName,
            null,
            $mailVars,
            (int)$currency->id,
            false,
            $customer->secure_key
        );
    }

}
