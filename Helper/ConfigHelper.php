<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */
namespace Sendinblue\Sendinblue\Helper;

/**
 * Class ConfigHelper
 * @package Sendinblue\Sendinblue\Helper
 */
class ConfigHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @TODO not really useful, move other scopeConfig calls inside
     * @param $req
     * @return mixed
     */
    public function getConfig($req)
    {
        return $this->scopeConfig->getValue('adminsample/' . $req);
    }

    /**
     * @TODO rewrite me please, this is only to avoid 500 error on adminhtml
     * @return bool
     */
    public function isServiceActive() {
        if ($this->scopeConfig->getValue('sendinblue/api_key_v3') !== null && $this->scopeConfig->getValue('sendinblue/api_key_status')) {
            return true;
        }
        return false;
    }
}
