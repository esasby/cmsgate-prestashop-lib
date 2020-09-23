<?php

/**
 * Created by PhpStorm.
 * User: nikit
 * Date: 30.09.2018
 * Time: 15:19
 */

namespace esas\cmsgate\view\admin;

use Context;
use esas\cmsgate\Registry;
use esas\cmsgate\view\admin\fields\ConfigField;
use esas\cmsgate\view\admin\fields\ConfigFieldCheckbox;
use esas\cmsgate\view\admin\fields\ConfigFieldList;
use esas\cmsgate\view\admin\fields\ConfigFieldTextarea;
use esas\cmsgate\view\admin\fields\ListOption;
use OrderState;

class ConfigFormPrestashop extends ConfigFormArray
{
    private $orderStatuses;

    /**
     * ConfigFieldsRenderWoo constructor.
     */
    public function __construct($formKey, $managedFields)
    {
        parent::__construct($formKey, $managedFields);
        foreach (OrderState::getOrderStates(1) as $statusArray) {
            $statusKey = $statusArray["id_order_state"];
            $statusName = $statusArray["name"];
            $this->orderStatuses[] = new ListOption($statusKey, $statusName);
        }
    }

    public function generate()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => Registry::getRegistry()->getTranslator()->translate($this->getFormKey()),
                    'icon' => 'icon-envelope'
                ),
                'input' => $this->generateFields(),
                'submit' => array(
                    'title' => Context::getContext()->getTranslator()->trans('Save', array(), 'Admin.Actions'),
                ),
//                'buttons' => array() //todok
            ),
        );
    }

    public function generateFields()
    {
        return parent::generate();
    }

    public function generateFieldArray(ConfigField $configField, $prestashopType, $addDefault = true)
    {
        $ret = array(
            'type' => $prestashopType,
            'label' => $configField->getKey(),
            'name' => $configField->getName(),
            'required' => $configField->isRequired(),
        );
        if ($addDefault && $configField->hasDefault()) {
            $ret['default_value'] = $configField->getDefault();
        }
        if ($configField->getValidationResult()->isValid())
            $ret['desc'] = $configField->getDescription();
        else {
            $ret['desc'] = [
                $configField->getDescription(),
                $configField->getValidationResult()->getErrorTextSimple()]; //добавляем текст ошибки к описанию поля
        }
        return $ret;
    }


    public function generateTextField(ConfigField $configField)
    {
        return $this->generateFieldArray($configField, "text");
    }

    public function generateTextAreaField(ConfigFieldTextarea $configField)
    {
        $ret = $this->generateFieldArray($configField, "textarea");
    }

    public function generateCheckboxField(ConfigFieldCheckbox $configField)
    {
        $ret = $this->generateFieldArray($configField, "switch");
        $ret['is_bool'] = true;
        $ret['values'] = array(
            array(
                'id' => 'active_on',
                'value' => true,
                'label' => Context::getContext()->getTranslator()->trans('Enabled', array(), 'Admin.Global'),
            ),
            array(
                'id' => 'active_off',
                'value' => false,
                'label' => Context::getContext()->getTranslator()->trans('Disabled', array(), 'Admin.Global'),
            )
        );
        return $ret;
    }

    public function generateListField(ConfigFieldList $configField)
    {
        $ret = $this->generateFieldArray($configField, "select", false);
        foreach ($configField->getOptions() as $option)
            $options[$option->getValue()] = $option->getName();
        $ret['options']  = array(
            'list' => $options,
        );
        return $ret;
    }

    /**
     * @return ListOption[]
     */
    public function createStatusListOptions()
    {
        return $this->orderStatuses;
    }

}