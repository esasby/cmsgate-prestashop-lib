<?php


namespace esas\cmsgate\prestashop;

use Configuration;
use esas\cmsgate\CmsConnectorPrestashop;
use esas\cmsgate\Registry;
use esas\cmsgate\utils\Logger;
use esas\cmsgate\view\admin\AdminViewFields;
use esas\cmsgate\view\admin\ConfigForm;
use esas\cmsgate\view\admin\ConfigFormPrestashop;
use esas\cmsgate\view\ViewBuilderPrestashop;
use Exception;
use HelperForm;
use Language;
use OrderState;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

use PaymentModule;
use Shop;
use Tools;
use Validate;

class CmsgatePaymentModule extends PaymentModule
{
    protected $_html = '';


    public function __construct()
    {
        $this->name = Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName();
        $this->tab = 'payments_gateways';
        $this->version = Registry::getRegistry()->getModuleDescriptor()->getVersion()->getVersion();
        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);
        $this->author = Registry::getRegistry()->getModuleDescriptor()->getVendor()->getFullName();
        $this->bootstrap = true;
        $this->need_instance = 0; // а может все-таки 1?
        $this->is_eu_compatible = 1;
        parent::__construct();

        $this->displayName = Registry::getRegistry()->getTranslator()->translate(AdminViewFields::ADMIN_PAYMENT_METHOD_NAME);
        $this->description = Registry::getRegistry()->getTranslator()->translate(AdminViewFields::ADMIN_PAYMENT_METHOD_DESCRIPTION);
        $this->confirmUninstall = $this->trans('Are you sure about removing these details?', array(), 'Modules.Wirepayment.Admin'); //todo

        foreach (Registry::getRegistry()->getConfigFormsArray() as $configForm) {
            foreach ($configForm->getManagedFields()->getFieldsToRender() as $configField) {
                if ($configField->isRequired() && empty($configField->getValue()))
                    $this->warning = $this->l('Field [' . $configField->getName() . '] is required.');
            }
        }
    }


    public function getContent()
    {
        try {
            $postedConfigForm = null;
            foreach (Registry::getRegistry()->getConfigFormsArray() as $configForm) {
                if (Tools::isSubmit(self::getSubmitAction($configForm))) {
                    $postedConfigForm = $configForm;
                    break;
                }
            }
            if ($postedConfigForm != null) {
                $postedConfigForm->validate();
                $postedConfigForm->save();
                $this->_html .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Global'));
            } else {
                $this->_html .= '<br />';
            }

        } catch (Throwable $e) {
            Logger::getLogger("getContent")->error("Exception", $e);
        } catch (Exception $e) { // для совместимости с php 5
            Logger::getLogger("getContent")->error("Exception", $e);
        }

        $this->_html .= $this->renderMessages();
        $this->_html .= ViewBuilderPrestashop::elementPaymentMethodDescription();
        foreach (Registry::getRegistry()->getConfigFormsArray() as $configForm) {
            $this->_html .= $this->renderForm($configForm);
        }
        return $this->_html;
    }

    public function renderMessages()
    {
        $ret = "";
        $messages = Registry::getRegistry()->getMessenger()->getInfoMessagesArray();
        if (!empty($messages)) {
            foreach ($messages as $message) {
                if (version_compare(_PS_VERSION_, '1.7.5.0', '<')) {
                    $ret .= $this->displayConfirmation($message);
                } else
                    $ret .= $this->displayInformation($message);
            }
        }
        $messages = Registry::getRegistry()->getMessenger()->getWarnMessagesArray();
        if (!empty($messages)) {
            foreach ($messages as $message)
                $ret .= $this->displayWarning($message);
        }
        $messages = Registry::getRegistry()->getMessenger()->getErrorMessagesArray();
        if (!empty($messages)) {
            foreach ($messages as $message)
                $ret .= $this->displayError($message);
        }
        return $ret;
    }


    /**
     * @param ConfigForm $configForm
     * @return string
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function renderForm($configForm)
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->bootstrap = true;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = self::getSubmitAction($configForm);
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues($configForm),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm([$configForm->generate()]);
    }

    /**
     * Генерация уникального имени для submit
     * @param $configForm
     * @return string
     */
    private static function getSubmitAction($configForm)
    {
        return 'btnSubmit' . $configForm->getFormKey();
    }

    /**
     * @param ConfigForm $configForm
     * @return array
     */
    public function getConfigFieldsValues($configForm)
    {
        $ret = array();
        foreach ($configForm->getManagedFields()->getFieldsToRender() as $configField) {
            $ret[$configField->getKey()] = Tools::getValue($configField->getKey(), $configField->getValue(true)); //скорее всего будет достаточно просто configField->getValue()
        }
        return $ret;
    }

    public function install()
    {
        $ret = parent::install();
        if (!$this->installOrderState()) {
            return false;
        }
        return $ret;
    }

    public function installOrderState()
    {
        if (!Configuration::get(CmsConnectorPrestashop::CMSGATE_ORDER_INITIAL_STATE)
            || !Validate::isLoadedObject(new OrderState(Configuration::get(CmsConnectorPrestashop::CMSGATE_ORDER_INITIAL_STATE)))) {
            $order_state = new OrderState();
            $order_state->name = array();
            foreach (Language::getLanguages() as $language) {
                $order_state->name[$language['id_lang']] = 'New order';
            }
            $order_state->send_email = false;
            $order_state->color = '#00a7bd';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = false;
            $order_state->invoice = false;
            $order_state->module_name = $this->name;
            if ($order_state->add()) {
                //todo add icon
            }
            Configuration::updateValue(CmsConnectorPrestashop::CMSGATE_ORDER_INITIAL_STATE, (int)$order_state->id);
        }
        return true;
    }

    /**
     * @param $controller
     * @return PaymentOption
     */
    public function createDefaultPaymentOption($controller) {
        $paymentOption = new PaymentOption();
        $paymentOption->setModuleName($this->name)
            ->setCallToActionText(Registry::getRegistry()->getConfigWrapper()->getPaymentMethodName())
            ->setAction($this->context->link->getModuleLink($this->name, $controller, array(), true))
            ->setAdditionalInformation(Registry::getRegistry()->getConfigWrapper()->getPaymentMethodDetails() . ViewBuilderPrestashop::elementSandboxMessage());
        return $paymentOption;
    }
}