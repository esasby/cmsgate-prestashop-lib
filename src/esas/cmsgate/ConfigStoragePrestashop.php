<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 15.07.2019
 * Time: 13:14
 */

namespace esas\cmsgate;

use Bitrix\Sale\Internals\PaySystemActionTable;
use Configuration;
use Exception;

class ConfigStoragePrestashop extends ConfigStorageCms
{
    /**
     * @param $key
     * @return string
     * @throws Exception
     */
    public function getConfig($key)
    {
        return Configuration::get($key);
    }

    /**
     * @param $cmsConfigValue
     * @return bool
     * @throws Exception
     */
    public function convertToBoolean($cmsConfigValue)
    {
        return $cmsConfigValue == 'Y' || $cmsConfigValue == '1' || $cmsConfigValue == "true";
    }

    public function saveConfig($key, $value)
    {
        Configuration::updateValue($key, $value);
    }

    public function createCmsRelatedKey($key)
    {
        return strtoupper(Registry::getRegistry()->getPaySystemName() . "_" . $key);
    }


}