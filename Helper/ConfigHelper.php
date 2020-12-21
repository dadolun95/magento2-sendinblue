<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */
namespace Sendinblue\Sendinblue\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class ConfigHelper
 * @package Sendinblue\Sendinblue\Helper
 */
class ConfigHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SMTP_CONFIG_MAPPING = [
        'api_smtp_status' => 'smtp/general/enabled',
        'smtp_authentication' => 'smtp/configuration_option/authentication',
        'smtp_username' => 'smtp/configuration_option/username',
        'smtp_password' => 'smtp/configuration_option/password',
        'smtp_host' => 'smtp/configuration_option/host',
        'smtp_port' => 'smtp/configuration_option/port',
        'smtp_tls' => 'smtp/configuration_option/protocol'
    ];

    /**
     * @var ConfigInterface
     */
    protected $resourceConfig;
    /**
     * @var \Mageplaza\Smtp\Model\Config\Source\Protocol
     */
    protected $smtpProtocols;
    /**
     * @var \Mageplaza\Smtp\Model\Config\Source\Authentication
     */
    protected $smtpAuthentications;

    /**
     * ConfigHelper constructor.
     * @param ConfigInterface $resourceConfig
     * @param \Mageplaza\Smtp\Model\Config\Source\Protocol $smtpProtocols
     * @param \Mageplaza\Smtp\Model\Config\Source\Authentication $smtpAuthentications
     * @param Context $context
     */
    public function __construct(
        ConfigInterface $resourceConfig,
        \Mageplaza\Smtp\Model\Config\Source\Protocol $smtpProtocols,
        \Mageplaza\Smtp\Model\Config\Source\Authentication $smtpAuthentications,
        Context $context
    )
    {
        $this->resourceConfig = $resourceConfig;
        $this->smtpProtocols = $smtpProtocols;
        $this->smtpAuthentications = $smtpAuthentications;
        parent::__construct($context);
    }

    /**
     * Check if sendinblue is authenticated
     *
     * @return bool
     */
    public function isServiceActive() {
        if ($this->getData('api_key_v3') !== null && $this->getData('api_key_status')) {
            return true;
        }
        return false;
    }

    /**
     * Get module config
     *
     * @param $val
     * @return mixed
     */
    public function getData($val)
    {
        return $this->scopeConfig->getValue('sendinblue/sendinblue/' . $val, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get module config
     *
     * @param $val
     * @return mixed
     */
    public function getFlag($val)
    {
        return $this->scopeConfig->isSetFlag('sendinblue/sendinblue/' . $val, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @TODO Adminhtml view is not updated when changes come on this method. Should migrate sib.phtml to system.xml or try something different
     * Update module config
     *
     * @param $key
     * @param $value
     */
    public function updateData($key, $value) {
        $this->resourceConfig->saveConfig('sendinblue/sendinblue/' . $key, $value, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, \Magento\Store\Model\Store::DEFAULT_STORE_ID);
        if (strpos($key, 'smtp')) {
            $this->updateSmtpData($key, $value);
        }
    }

    /**
     * @param $key
     * @param $value
     */
    protected function updateSmtpData($key, $value) {
        $configMapping = self::SMTP_CONFIG_MAPPING;
        $smtpConfigPath = $configMapping[$key];
        if (is_string($smtpConfigPath)) {
            $this->resourceConfig->saveConfig($smtpConfigPath, $value, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, \Magento\Store\Model\Store::DEFAULT_STORE_ID);
        }
    }

    /**
     * Delete SMTP entry
     */
    public function resetSmtpDetail()
    {
        $this->updateData('api_smtp_status', 0);
        $this->updateData('smtp_authentication', '');
        $this->updateData('smtp_username', '');
        $this->updateData('smtp_password', '');
        $this->updateData('smtp_host', '');
        $this->updateData('smtp_port', '');
        $this->updateData('smtp_tls', '');
        $this->updateData('smtp_option', '');
        $this->updateData('relay_data_status', '');
    }

    /**
     * Method to factory reset the configs on database.
     */
    public function resetDefaultValues()
    {
        $this->updateData('ord_track_status', 0);
        $this->updateData('order_import_status', 0);
        $this->updateData('api_smtp_status', 0);
        $this->updateData('first_request', '');
        $this->updateData('api_key_v3', '');
        $this->updateData('selected_list_data', '');
        $this->updateData('confirm_type', '');
        $this->updateData('doubleoptin_redirect', '');
        $this->updateData('optin_url_check', '');
        $this->updateData('final_confirm_email', '');
        $this->updateData('optin_list_id', '');
        $this->updateData('final_template_id', '');
        $this->updateData('api_sms_shipment_status', 0);
        $this->updateData('api_sms_campaign_status', 0);
        $this->updateData('api_sms_order_status', 0);
        $this->updateData('sib_automation_key', '');
        $this->updateData('sib_track_status', 0);
        $this->updateData('sib_automation_enable', '');
    }

    /**
     * @TODO move defaults on core config and change on each sendin_config_lang update
     * @return array
     */
    public function getDefaultSubscriptionAttributes()
    {
        $userLanguage = $this->getData('sendin_config_lang');
        if ($userLanguage == 'fr') {
            $attributesName = array(
                'PRENOM' => 'firstname',
                'NOM' => 'lastname',
                'MAGENTO_LANG' => 'created_in',
                'CLIENT' => 'client',
                'SMS' => 'telephone',
                'COMPANY' => 'company',
                'CITY' => 'city',
                'COUNTRY_ID' => 'country_id',
                'POSTCODE' => 'postcode',
                'STREET' => 'street',
                'REGION' => 'region',
                'STORE_ID' => 'store_id'
            );
        } else {
            $attributesName = array(
                'NAME' => 'firstname',
                'SURNAME' => 'lastname',
                'MAGENTO_LANG' => 'created_in',
                'CLIENT' => 'client',
                'SMS' => 'telephone',
                'COMPANY' => 'company',
                'CITY' => 'city',
                'COUNTRY_ID' => 'country_id',
                'POSTCODE' => 'postcode',
                'STREET' => 'street',
                'REGION' => 'region',
                'STORE_ID' => 'store_id'
            );
        }
        return $attributesName;
    }

    /**
     * Fetch attributes name and type on Sendinblue platform.
     * This is necessary for the Magento to add subscriber's details.
     *
     * @param string $config
     * @return array
     */
    public function normalizeSubscriptionAttributes($config = '')
    {
        if (!empty($config)) {
            $langConfig = $config['lang'];
        } else {
            $langConfig = $this->getData('sendin_config_lang');
        }
        $attributesType = array(
            array("name" => "MAGENTO_LANG", "category" => "normal", "type" => "text"),
            array("name" => "CLIENT", "category" => "normal", "type" => "float"),
            array("name" => "SMS", "category" => "normal", "type" => "text"),
            array("name" => "COMPANY", "category" => "normal", "type" => "text"),
            array("name" => "CITY", "category" => "normal", "type" => "text"),
            array("name" => "COUNTRY_ID", "category" => "normal", "type" => "text"),
            array("name" => "POSTCODE", "category" => "normal", "type" => "float"),
            array("name" => "STREET", "category" => "normal", "type" => "text"),
            array("name" => "REGION", "category" => "normal", "type" => "text"),
            array("name" => "STORE_ID", "category" => "normal", "type" => "float"),
        );
        if ($langConfig == 'fr') {
            $attributesType[] = array("name" => "PRENOM", "category" => "normal", "type" => "text");
            $attributesType[] = array("name" => "NOM", "category" => "normal", "type" => "text");
        } else {
            $attributesType[] = array("name" => "NAME", "category" => "normal", "type" => "text");
            $attributesType[] = array("name" => "SURNAME", "category" => "normal", "type" => "text");
        }
        return $attributesType;
    }

    /**
     * Get Calculated attributes
     *
     * @return array
     */
    public function getCalculatedSubscriptionAttributes()
    {
        return array(
            array("name" => "MAGENTO_LAST_30_DAYS_CA", "category" => "calculated", "value" => "SUM[ORDER_PRICE,ORDER_DATE,>,NOW(-30)]"),
            array("name" => "MAGENTO_ORDER_TOTAL", "category" => "calculated", "value" => "COUNT[ORDER_ID]"),
            array("name" => "MAGENTO_CA_USER", "category" => "calculated", "value" => "SUM[ORDER_PRICE]")
        );
    }

    /**
     * Get Global attributes
     *
     * @return array
     */
    public function getGlobalSubscriptionAttributes()
    {
        return array(
            array("name" => "MAGENTO_CA_LAST_30DAYS", "category" => "global", "value" => "SUM[MAGENTO_LAST_30_DAYS_CA]"),
            array("name" => "MAGENTO_CA_TOTAL", "category" => "global", "value" => "SUM[MAGENTO_CA_USER]"),
            array("name" => "MAGENTO_ORDERS_COUNT", "category" => "global", "value" => "SUM[MAGENTO_ORDER_TOTAL]")
        );
    }

    /**
     * Fetch all Transactional Attributes
     * on Sendinblue platform. This is necessary for the Magento to add subscriber's details.
     *
     * @return array
     */
    public function getTransactionalSubscriptionAttributes()
    {
        return array(
            array("name" => "ORDER_ID", "category" => "transactional", "type" => "id"),
            array("name" => "ORDER_DATE", "category" => "transactional", "type" => "date"),
            array("name" => "ORDER_PRICE", "category" => "transactional", "type" => "float")
        );
    }

    /**
     * @TODO move templates in core_config_data table
     * @param $lang
     * @return string
     */
    public function getOptinDefaultTemplate($lang)
    {
        if ($lang == "fr") {
            return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head> <meta content="text/html; charset=utf-8" http-equiv="Content-Type"> <title>{title}</title> </head> <body style="font-family: Arial, Helvetica, sans-serif;font-size: 12px;color: #222;"> <div class="moz-forward-container"> <br><table cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#ffffff"> <tbody> <tr style="border-collapse:collapse;"> <td align="center" style="border-collapse:collapse;"> <table cellspacing="0" cellpadding="0" border="0" width="570"> <tbody> <tr> <td height="20" style="line-height:0; font-size:0;"><img width="0" height="0" alt="{shop_name}" src="{shop_logo}"></td></tr></tbody> </table><table cellpadding="0" cellspacing="0" border="0" width="540"><tbody><tr><td style="line-height:0; font-size:0;" height="20"><div style="font-family:arial,sans-serif; color:#61a6f3; font-size:20px; font-weight:bold; line-height:28px;">Confirmez votre inscription</div></td></tr></tbody></table><table cellspacing="0" cellpadding="0" border="0" width="540"><tbody><tr><td align="left"><div style="font-family:arial,sans-serif; font-size:14px; margin:0; line-height:24px; color:#555555;"><br>Voulez vous recevoir les newsletters de{site_name}?<br><br><a href="{double_optin}" style="color:#ffffff;display:inline-block;font-family:Arial,sans-serif;width:auto;white-space:nowrap;min-height:32px;margin:5px 5px 0 0;padding:0 22px;text-decoration:none;text-align:center;font-weight:bold;font-style:normal;font-size:15px;line-height:32px;border:0;border-radius:4px;vertical-align:top;background-color:#3276b1" target="_blank"><span style="display:inline;font-family:Arial,sans-serif;text-decoration:none;font-weight:bold;font-style:normal;font-size:15px;line-height:32px;border:none;background-color:#3276b1;color:#ffffff">Oui, je confirme mon inscription</span></a><br><br>Si vous recevez cet email par erreur, vous pouvez simplement le supprimer. Vous ne serez pas inscrit à la newsletter si vous ne cliquez pas sur le lien de confirmation ci-dessus.<br><br>{site_name}</div></td></tr></tbody></table> </td></tr></tbody> </table> <br></div></body></html>';
        }
        return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head> <meta content="text/html; charset=utf-8" http-equiv="Content-Type"> <title>{title}</title> </head> <body style="font-family: Arial, Helvetica, sans-serif;font-size: 12px;color: #222;"> <div class="moz-forward-container"> <br><table cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#ffffff"> <tbody> <tr style="border-collapse:collapse;"> <td align="center" style="border-collapse:collapse;"> <table cellspacing="0" cellpadding="0" border="0" width="570"> <tbody> <tr> <td height="20" style="line-height:0; font-size:0;"><img width="0" height="0" alt="{shop_name}" src="{shop_logo}"></td></tr></tbody> </table><table cellpadding="0" cellspacing="0" border="0" width="540"><tbody><tr><td style="line-height:0; font-size:0;" height="20"><div style="font-family:arial,sans-serif; color:#61a6f3; font-size:20px; font-weight:bold; line-height:28px;">Please confirm your subscription</div></td></tr></tbody></table><table cellspacing="0" cellpadding="0" border="0" width="540"><tbody><tr><td align="left"><div style="font-family:arial,sans-serif; font-size:14px; margin:0; line-height:24px; color:#555555;"><br>Do you want to receive newsletters from{site_name}?<br><br><a href="{double_optin}" style="color:#ffffff;display:inline-block;font-family:Arial,sans-serif;width:auto;white-space:nowrap;min-height:32px;margin:5px 5px 0 0;padding:0 22px;text-decoration:none;text-align:center;font-weight:bold;font-style:normal;font-size:15px;line-height:32px;border:0;border-radius:4px;vertical-align:top;background-color:#3276b1" target="_blank"><span style="display:inline;font-family:Arial,sans-serif;text-decoration:none;font-weight:bold;font-style:normal;font-size:15px;line-height:32px;border:none;background-color:#3276b1;color:#ffffff">Yes, subscribe me to this list.</span></a><br><br>If you received this email by mistake, simply delete it. You will not be subscribed to this list if you do not click the confirmation link above.<br><br>{site_name}</div></td></tr></tbody></table> </td></tr></tbody> </table> <br></div></body></html>';

    }

    /**
     * @TODO move templates in core_config_data table
     * @param $lang
     * @return string
     */
    public function getSendinDefaultTemplate($lang)
    {
        if ($lang == "fr") {
            return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><title>[Sendinblue] Alerte: Vos crédits SMS seront bientôt épuisés</title></head><body style="font-family: Arial, Helvetica, sans-serif;font-size: 12px;color: #222;"><div class="moz-forward-container"><br><table style="background-color:#ffffff" width="100%" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr style="border-collapse:collapse;"> <td style="border-collapse:collapse;" align="center"> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td valign="middle" align="left"> <h1 style="margin:0;color:#2f8bee;font-family:arial,sans-serif"><img src="http://img.sendinblue.com/14406/images/529f2339c6ece.png" alt="Sendinblue"></h1> </td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="540" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td align="left"> <div style="font-family:arial,sans-serif; color:#2f8bee; font-size:18px; font-weight:bold; margin:0 0 10px 0;">Bonjour,<br/><br/>Cet email est envoyé pour vous informer que vous n\'avez plus assez de crédits pour envoyer des SMS à partir de votre site Magento{site_name}.<br/><br/>Actuellement, vous avez{present_credit}crédits SMS.<br/><br/>Cordialement,<br/>L\'équipe de Sendinblue<br/> </div></td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr><tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="540" border="0" cellpadding="0" cellspacing="0"> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="10">&nbsp;</td></tr></tbody> </table> <table width="540" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="10">&nbsp;</td></tr><tr> <td valign="top" width="200" align="left"> <div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> <strong style="color:#2f8bee;">Sendinblue</strong></div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> 59 rue Beaubourg</div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> 75003 Paris - France</div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> T&eacute;l : 0899 25 30 61</div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> <a moz-do-not-send="true" href="http://www.sendinblue.com" style="color:#2f8bee;" target="_blank">www.sendinblue.com</a></div></td><td align="right" valign="top"><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:20px; color:#7e7e7e;"> <a href="http://www.facebook.com/SendinBlue" style="color:#2f8bee; text-decoration:none;" target="_blank"> <img alt="Facebook" src="https://my.sendinblue.com/public/upload/14406/images/523693143fe88.gif" style="border:none;"> </a> <a href="https://twitter.com/SendinBlue" style="color:#2f8bee; text-decoration:none;" target="_blank"> <img alt="Twitter" src="https://my.sendinblue.com/public/upload/14406/images/5236931746c01.gif" style="border:none;"> </a> <a href="http://www.linkedin.com/company/mailin" style="color:#2f8bee; text-decoration:none;" target="_blank"> <img alt="Linkedin" src="https://my.sendinblue.com/public/upload/14406/images/5236931ad253b.gif" style="border:none;"> </a> <a href="http://sendinblue.tumblr.com/" style="color:#2f8bee; text-decoration:none;" target="_blank">Blog</a></div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;"> &copy; 2014-2015 Sendinblue, tous droits r&eacute;serv&eacute;s. </div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;"> Ceci est un message automatique g&eacute;n&eacute;r&eacute; par Sendinblue. </div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;"> Ne pas y r&eacute;pondre, vous ne recevriez aucune r&eacute;ponse. </div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;"><a href="https://www.sendinblue.com/legal/antispampolicy" style="color:#7e7e7e;" target="_blank">Politique anti-spam &amp; emailing</a> | <a href="https://www.sendinblue.com/legal/generalterms" style="color:#7e7e7e;" target="_blank">Conditions g&eacute;n&eacute;rales de ventes</a></div></td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr><tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> </td></tr></tbody> </table> <br></div></body></html>';
        }
        return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><title>[Sendinblue] Alert: You do not have enough credits SMS</title></head><body style="font-family: Arial, Helvetica, sans-serif;font-size: 12px;color: #222;"><div class="moz-forward-container"><br><table style="background-color:#ffffff" width="100%" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr style="border-collapse:collapse;"> <td style="border-collapse:collapse;" align="center"> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td valign="middle" align="left"> <h1 style="margin:0;color:#2f8bee;font-family:arial,sans-serif"><img src="http://img.sendinblue.com/14406/images/529f2339c6ece.png" alt="Sendinblue"></h1> </td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="540" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td align="left"> <div style="font-family:arial,sans-serif; color:#2f8bee; font-size:18px; font-weight:bold; margin:0 0 10px 0;">Hello,<br/><br/>This email is sent to inform you that you do not have enough credits to send SMS from your Magento website{site_name}.<br/><br/>Actually, you have{present_credit}credits sms.<br/><br/>Regards,<br/>Sendinblue team<br/> </div></td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr><tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="540" border="0" cellpadding="0" cellspacing="0"> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="10">&nbsp;</td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> </table> <table width="540" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="10">&nbsp;</td></tr><tr> <td align="left" valign="top" width="200"> <div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> <strong style="color:#2f8bee;">Sendinblue</strong> </div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> 59 rue Beaubourg</div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> 75003 Paris - France</div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> Tél : 0899 25 30 61</div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> <a moz-do-not-send="true" href="http://www.sendinblue.com" style="color:#2f8bee;" target="_blank">www.sendinblue.com</a> </div></td><td align="right" valign="top"><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:20px; color:#7e7e7e;"> <a href="http://www.facebook.com/SendinBlue" style="color:#2f8bee; text-decoration:none;" target="_blank"> <img alt="Facebook" src="https://my.sendinblue.com/public/upload/14406/images/523693143fe88.gif" style="border:none;"> </a> <a href="https://twitter.com/SendinBlue" style="color:#2f8bee; text-decoration:none;" target="_blank"> <img alt="Twitter" src="https://my.sendinblue.com/public/upload/14406/images/5236931746c01.gif" style="border:none;"> </a> <a href="http://www.linkedin.com/company/mailin" style="color:#2f8bee; text-decoration:none;" target="_blank"> <img alt="Linkedin" src="https://my.sendinblue.com/public/upload/14406/images/5236931ad253b.gif" style="border:none;"> </a> <a href="http://sendinblue.tumblr.com/" style="color:#2f8bee; text-decoration:none;" target="_blank">Blog</a></div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;"> © 2014-2015 Sendinblue, all rights reserved.</div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;">This is an automatic message generated by Sendinblue.</div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;">Do not respond, you would not receive any answer.</div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;"><a href="https://www.sendinblue.com/legal/antispampolicy" style="color:#7e7e7e;" target="_blank">Anti-spam & emailing policy</a> | <a href="https://www.sendinblue.com/legal/generalterms" style="color:#7e7e7e;" target="_blank">General Terms and Conditions</a></div></td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr><tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> </td></tr></tbody> </table> <br></div></body></html>';
    }

    /**
     * @TODO move templates in core_config_data table
     * @param $lang
     * @return string
     */
    public function getSmtpDefaultTemplate($lang)
    {
        if ($lang == "fr") {
            return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><title>[Sendinblue SMTP] e-mail de test</title></head><body style="font-family: Arial, Helvetica, sans-serif;font-size: 12px;color: #222;"><div class="moz-forward-container"><br><table style="background-color:#ffffff" width="100%" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr style="border-collapse:collapse;"> <td style="border-collapse:collapse;" align="center"> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td valign="middle" align="left"> <h1 style="margin:0;color:#2f8bee;font-family:arial,sans-serif"><img src="http://img.sendinblue.com/14406/images/529f2339c6ece.png" alt="Sendinblue"></h1> </td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="540" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td align="left"> <div style="font-family:arial,sans-serif; color:#2f8bee; font-size:18px; font-weight:bold; margin:0 0 10px 0;">Cet e-mail a été envoyé via Sendinblue SMTP. <br/> Félicitations, la fonctionnalité Sendinblue SMTP est bien configurée. </div></td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr><tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="540" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td align="right"> <div style="font-family:arial,sans-serif; font-size:14px; color:#2f8bee; margin:0; font-weight:bold; line-height:18px;"> L\'&eacute;quipe de Sendinblue</div></td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="10">&nbsp;</td></tr></tbody> </table> <table width="540" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="10">&nbsp;</td></tr><tr> <td valign="top" width="200" align="left"> <div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> <strong style="color:#2f8bee;">Sendinblue</strong></div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> 59 rue Beaubourg</div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> 75003 Paris - France</div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> T&eacute;l : 0899 25 30 61</div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> <a moz-do-not-send="true" href="http://www.sendinblue.com" style="color:#2f8bee;" target="_blank">www.sendinblue.com</a></div></td><td align="right" valign="top"><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:20px; color:#7e7e7e;"> <a href="http://www.facebook.com/SendinBlue" style="color:#2f8bee; text-decoration:none;" target="_blank"> <img alt="Facebook" src="https://my.sendinblue.com/public/upload/14406/images/523693143fe88.gif" style="border:none;"> </a> <a href="https://twitter.com/SendinBlue" style="color:#2f8bee; text-decoration:none;" target="_blank"> <img alt="Twitter" src="https://my.sendinblue.com/public/upload/14406/images/5236931746c01.gif" style="border:none;"> </a> <a href="http://www.linkedin.com/company/mailin" style="color:#2f8bee; text-decoration:none;" target="_blank"> <img alt="Linkedin" src="https://my.sendinblue.com/public/upload/14406/images/5236931ad253b.gif" style="border:none;"> </a> <a href="http://sendinblue.tumblr.com/" style="color:#2f8bee; text-decoration:none;" target="_blank">Blog</a></div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;"> &copy; 2014-2015 Sendinblue, tous droits r&eacute;serv&eacute;s. </div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;"> Ceci est un message automatique g&eacute;n&eacute;r&eacute; par Sendinblue. </div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;"> Ne pas y r&eacute;pondre, vous ne recevriez aucune r&eacute;ponse. </div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;"><a href="https://www.sendinblue.com/legal/antispampolicy" style="color:#7e7e7e;" target="_blank">Politique anti-spam &amp; emailing</a> | <a href="https://www.sendinblue.com/legal/generalterms" style="color:#7e7e7e;" target="_blank">Conditions g&eacute;n&eacute;rales de ventes</a></div></td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr><tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> </td></tr></tbody> </table> <br></div></body></html>';
        }
        return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><title>[Sendinblue SMTP] test email</title></head><body style="font-family: Arial, Helvetica, sans-serif;font-size: 12px;color: #222;"><div class="moz-forward-container"><br><table style="background-color:#ffffff" width="100%" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr style="border-collapse:collapse;"> <td style="border-collapse:collapse;" align="center"> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td valign="middle" align="left"> <h1 style="margin:0;color:#2f8bee;font-family:arial,sans-serif"><img src="http://img.sendinblue.com/14406/images/529f2339c6ece.png" alt="Sendinblue"></h1> </td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="540" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td align="left"> <div style="font-family:arial,sans-serif; color:#2f8bee; font-size:18px; font-weight:bold; margin:0 0 10px 0;">This email has been sent using Sendinblue SMTP. <br/> Congratulations, your Sendinblue SMTP module has been set up well. </div></td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr><tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="540" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td align="right"> <div style="font-family:arial,sans-serif; font-size:14px; color:#2f8bee; margin:0; font-weight:bold; line-height:18px;">Sendinblue Team</div></td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="10">&nbsp;</td></tr></tbody> </table> <table width="540" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="10">&nbsp;</td></tr><tr> <td valign="top" width="200" align="left"> <div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> <strong style="color:#2f8bee;">Sendinblue</strong></div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> 59 rue Beaubourg</div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> 75003 Paris - France</div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> T&eacute;l : 0899 25 30 61</div><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:16px; color:#7e7e7e;"> <a moz-do-not-send="true" href="http://www.sendinblue.com" style="color:#2f8bee;" target="_blank">www.sendinblue.com</a></div></td><td align="right" valign="top"><div style="font-family:arial,sans-serif; font-size:12px; margin:0; line-height:20px; color:#7e7e7e;"> <a href="http://www.facebook.com/SendinBlue" style="color:#2f8bee; text-decoration:none;" target="_blank"> <img alt="Facebook" src="https://my.sendinblue.com/public/upload/14406/images/523693143fe88.gif" style="border:none;"> </a> <a href="https://twitter.com/SendinBlue" style="color:#2f8bee; text-decoration:none;" target="_blank"> <img alt="Twitter" src="https://my.sendinblue.com/public/upload/14406/images/5236931746c01.gif" style="border:none;"> </a> <a href="http://www.linkedin.com/company/mailin" style="color:#2f8bee; text-decoration:none;" target="_blank"> <img alt="Linkedin" src="https://my.sendinblue.com/public/upload/14406/images/5236931ad253b.gif" style="border:none;"> </a> <a href="http://sendinblue.tumblr.com/" style="color:#2f8bee; text-decoration:none;" target="_blank">Blog</a></div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;"> © 2014-2015 Sendinblue, all rights reserved.</div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;">This is an automatic message generated by Sendinblue.</div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;">Do not respond, you wouldn\'t receive any answer.</div><div style="font-family:arial,sans-serif; font-size:10px; margin:0; line-height:14px; color:#7e7e7e;"><a href="https://www.sendinblue.com/legal/antispampolicy" style="color:#7e7e7e;" target="_blank">Anti-spam & emailing policy</a> | <a href="https://www.sendinblue.com/legal/generalterms" style="color:#7e7e7e;" target="_blank">General Terms and Conditions</a></div></td></tr></tbody> </table> <table width="570" border="0" cellpadding="0" cellspacing="0"> <tbody> <tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr><tr> <td style="line-height:0; font-size:0;" height="20">&nbsp;</td></tr></tbody> </table> </td></tr></tbody> </table> <br></div></body></html>';
    }

    /**
     * Checks whether the Sendinblue API key and the Sendinblue subscription form is enabled
     * and returns the true|false accordingly.
     *
     * @return bool
     */
    public function syncSetting() {
        $keyStatus = $this->getFlag('api_key_status');
        $subsStatus = $this->getFlag('subscribe_setting');
        if ($keyStatus && $subsStatus) {
            return 1;
        }
        return 0;
    }

}
