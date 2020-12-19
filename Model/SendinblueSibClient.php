<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */
namespace Sendinblue\Sendinblue\Model;

use \GuzzleHttp\Client as HttpClient;
use \SendinBlue\Client\Configuration as ClientConfiguration;
use \SendinBlue\Client\Api\ContactsApi;
use \SendinBlue\Client\Api\AccountApi;
use \SendinBlue\Client\Api\AttributesApi;
use \SendinBlue\Client\Api\TransactionalSMSApi;
use \SendinBlue\Client\Api\TransactionalEmailsApi;
use \SendinBlue\Client\Api\SMSCampaignsApi;
use \SendinBlue\Client\Api\SendersApi;
use \SendinBlue\Client\Model\RequestContactImport;
use \SendinBlue\Client\Model\CreateAttribute;
use \SendinBlue\Client\Model\CreateUpdateFolder;
use \SendinBlue\Client\Model\CreateContact;
use \SendinBlue\Client\Model\SendTransacSms;
use \SendinBlue\Client\Model\UpdateContact;
use \SendinBlue\Client\Model\SendSmtpEmail;
use \SendinBlue\Client\Model\CreateSmsCampaign;

/**
 * Class SendinblueSibClient
 * @package Sendinblue\Sendinblue\Model
 */
class SendinblueSibClient
{
    const RESPONSE_CODE_OK = 200;
    const RESPONSE_CODE_CREATED = 201;
    const RESPONSE_CODE_ACCEPTED = 202;

    private $apiKey;
    private $lastResponseCode;
    private $config;

    /**
     * @return \SendinBlue\Client\Model\GetAccount
     * @throws \SendinBlue\Client\ApiException
     */
    public function getAccount()
    {
        $apiInstance = new AccountApi(
            new HttpClient(),
            $this->config
        );
        $result = $apiInstance->getAccountWithHttpInfo();
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @param $key
     * @return $this
     */
    public function setApiKey($key)
    {
        $this->apiKey = trim($key);
        $this->config = ClientConfiguration::getDefaultConfiguration()
            ->setApiKey('api-key', $this->apiKey);
        return $this;
    }

    /**
     * @param $data
     * @return \SendinBlue\Client\Model\GetLists
     * @throws \SendinBlue\Client\ApiException
     */
    public function getLists($data)
    {
        $apiInstance = new ContactsApi(
            new HttpClient(),
            $this->config
        );
        $result = $apiInstance->getLists($data['limit'], $data['offset']);
        $this->lastResponseCode = $result[1];
        $result = (array)$result[0];
    }


    /**
     * @param $folder
     * @param $data
     * @return \SendinBlue\Client\Model\GetFolderLists
     * @throws \SendinBlue\Client\ApiException
     */
    public function getListsInFolder($folder, $data)
    {
        $apiInstance = new ContactsApi(
            new HttpClient(),
            $this->config
        );
        $result = $apiInstance->getFolderLists($folder, $data['limit'], $data['offest']);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @param $data
     * @return \SendinBlue\Client\Model\CreatedProcessId
     * @throws \SendinBlue\Client\ApiException
     */
    public function importUsers($data)
    {
        $apiInstance = new ContactsApi(
            new HttpClient(),
            $this->config
        );
        $requestContactImport = new RequestContactImport($data);
        $result = $apiInstance->importContacts($requestContactImport);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @param int $folder
     * @return array
     * @throws \SendinBlue\Client\ApiException
     */
    public function getAllLists($folder = 0)
    {
        $lists = array("lists" => array(), "count" => 0);
        $offset = 0;
        $limit = 50;
        do {
            if ($folder > 0) {
                $list_data = $this->getListsInFolder($folder, array('limit' => $limit, 'offset' => $offset));
            } else {
                $list_data = $this->getLists(array('limit' => $limit, 'offset' => $offset));
            }
            if ($list_data->getLists()) {
                $list_data = array("lists" => array(), "count" => 0);
            }
            $lists["lists"] = array_merge($lists["lists"], $list_data->getLists());
            $offset += 50;
        } while (count($lists["lists"]) < $list_data->getCount());
        $lists["count"] = $list_data->getCounzt();
        return $lists;
    }

    /**
     * @throws \SendinBlue\Client\ApiException
     */
    public function getAttributes()
    {
        $apiInstance = new AttributesApi(
            new HttpClient(),
            $this->config
        );
        $result = $apiInstance->getAttributes();
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @param $type
     * @param $name
     * @param $data
     * @return array
     * @throws \SendinBlue\Client\ApiException
     */
    public function createAttribute($type, $name, $data)
    {
        $apiInstance = new AttributesApi(
            new HttpClient(),
            $this->config
        );
        $attributeData = $createAttribute = new CreateAttribute($data);
        $result = $apiInstance->createAttributeWithHttpInfo($type, $name, $attributeData);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @param $data
     * @return \SendinBlue\Client\Model\GetFolders
     * @throws \SendinBlue\Client\ApiException
     */
    public function getFolders($data)
    {
        $apiInstance = new ContactsApi(
            new HttpClient(),
            $this->config
        );
        $result = $apiInstance->getFolders($data['limit'], $data['offset']);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @return array
     * @throws \SendinBlue\Client\ApiException
     */
    public function getFoldersAll()
    {
        $folders = array("folders" => array(), "count" => 0);
        $offset = 0;
        $limit = 50;
        do {
            $folder_data = $this->getFolders(array('limit' => $limit, 'offset' => $offset));
            $folders["folders"] = array_merge($folders["folders"], $folder_data["folders"]);
            $offset += 50;
        } while (count($folders["folders"]) < $folder_data["count"]);
        $folders["count"] = $folder_data["count"];
        return $folders;
    }

    /**
     * @param $data
     * @return \SendinBlue\Client\Model\CreateModel
     * @throws \SendinBlue\Client\ApiException
     */
    public function createFolder($data)
    {
        $apiInstance = new ContactsApi(
            new HttpClient(),
            $this->config
        );
        $createFolder = new CreateUpdateFolder($data);
        $result = $apiInstance->createFolder($createFolder);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @param $data
     * @return \SendinBlue\Client\Model\GetLists
     * @throws \SendinBlue\Client\ApiException
     */
    public function createList($data)
    {
        $apiInstance = new ContactsApi(
            new HttpClient(),
            $this->config
        );
        $result = $apiInstance->getLists($data['limit'], $data['offset']);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @param $email
     * @return \SendinBlue\Client\Model\GetExtendedContactDetails
     * @throws \SendinBlue\Client\ApiException
     */
    public function getUser($email)
    {
        $apiInstance = new ContactsApi(
            new HttpClient(),
            $this->config
        );
        $result = $apiInstance->getContactInfo(urlencode($email));
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @param $data
     * @return \SendinBlue\Client\Model\CreateUpdateContactModel
     * @throws \SendinBlue\Client\ApiException
     */
    public function createUser($data)
    {
        $apiInstance = new ContactsApi(
            new HttpClient(),
            $this->config
        );
        $createContact = new CreateContact($data);
        $result = $apiInstance->createContact($createContact);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @param $email
     * @param $data
     * @return array
     * @throws \SendinBlue\Client\ApiException
     */
    public function updateUser($email, $data)
    {
        $apiInstance = new ContactsApi(
            new HttpClient(),
            $this->config
        );
        $updateContact = new UpdateContact($data);
        $result = $apiInstance->updateContactWithHttpInfo($email, $updateContact);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @param $data
     * @return \SendinBlue\Client\Model\SendSms
     * @throws \SendinBlue\Client\ApiException
     */
    public function sendSms($data)
    {
        $apiInstance = new TransactionalSMSApi(
            new HttpClient(),
            $this->config
        );
        $sendTransacSms = new SendTransacSms();
        $result = $apiInstance->sendTransacSms($sendTransacSms);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @param $data
     * @return \SendinBlue\Client\Model\CreateSmtpEmail
     * @throws \SendinBlue\Client\ApiException
     */
    public function sendTransactionalTemplate($data)
    {
        $apiInstance = new TransactionalEmailsApi(
            new HttpClient(),
            $this->config
        );
        $sendSmtpEmail = new SendSmtpEmail($data);
        $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @param $data
     * @return \SendinBlue\Client\Model\CreateModel
     * @throws \SendinBlue\Client\ApiException
     */
    public function createSmsCampaign($data)
    {
        $apiInstance = new SMSCampaignsApi(
            new HttpClient(),
            $this->config
        );
        $createSmsCampaign = new CreateSmsCampaign($data);
        $result = $apiInstance->createSmsCampaign($createSmsCampaign);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }


    /**
     * @param $data
     * @return \SendinBlue\Client\Model\GetSmtpTemplates
     * @throws \SendinBlue\Client\ApiException
     */
    public function getEmailTemplates($data)
    {
        $apiInstance = new TransactionalEmailsApi(
            new HttpClient(),
            $this->config
        );
        $result = $apiInstance->getSmtpTemplates($data['templateStatus'], $data['limit'], $data['offset']);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @return array
     * @throws \SendinBlue\Client\ApiException
     */
    public function getAllEmailTemplates()
    {
        $templates = array("templates" => array(), "count" => 0);
        $offset = 0;
        $limit = 50;
        do {
            $template_data = $this->getEmailTemplates(array('templateStatus' => 'true', 'limit' => $limit, 'offset' => $offset));
            if (!isset($template_data["templates"])) {
                $template_data = array("templates" => array(), "count" => 0);
            }
            $templates["templates"] = array_merge($templates["templates"], $template_data["templates"]);
            $offset += 50;
        } while (count($templates["templates"]) < $template_data["count"]);
        $templates["count"] = count($templates["templates"]);
        return $templates;
    }

    /**
     * @param $id
     * @return \SendinBlue\Client\Model\GetSmtpTemplateOverview
     * @throws \SendinBlue\Client\ApiException
     */
    public function getTemplateById($id)
    {
        $apiInstance = new TransactionalEmailsApi(
            new HttpClient(),
            $this->config
        );
        $result = $apiInstance->getSmtpTemplate($id);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @param $data
     * @return \SendinBlue\Client\Model\CreateSmtpEmail
     * @throws \SendinBlue\Client\ApiException
     */
    public function sendEmail($data)
    {
        $apiInstance = new TransactionalEmailsApi(
            new HttpClient(),
            $this->config
        );
        $sendSmtpEmail = new SendSmtpEmail($data);
        $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @return \SendinBlue\Client\Model\GetSendersList
     * @throws \SendinBlue\Client\ApiException
     */
    public function getSenders()
    {
        $apiInstance = new SendersApi(
            new HttpClient(),
            $this->config
        );
        $result = $apiInstance->getSenders();
        $this->lastResponseCode = $result[1];
        return $result[0];
    }

    /**
     * @return int
     */
    public function getLastResponseCode()
    {
        return $this->lastResponseCode;
    }
}
