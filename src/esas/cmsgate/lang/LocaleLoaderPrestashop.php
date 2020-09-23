<?php
/**
 * Created by PhpStorm.
 * User: nikit
 * Date: 27.09.2018
 * Time: 13:09
 */

namespace esas\cmsgate\lang;


use Context;

class LocaleLoaderPrestashop extends LocaleLoaderCms
{
    public function getLocale()
    {
        return Context::getContext()->language->iso_code;
    }


    public function getCmsVocabularyDir()
    {
        return dirname(__FILE__);
    }
}