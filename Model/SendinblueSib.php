<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */
namespace Sendinblue\Sendinblue\Model;

use Magento\Backend\Model\Auth\Session as BackendAuthSession;
use Magento\Customer\Api\AddressRepositoryInterface as CustomerAddressRepository;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Sendinblue\Sendinblue\Helper\ConfigHelper;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as NewsletterSubscriberCollectionFactory;

/**
 * @TODO this class is too long, split in more Model classes
 * Class SendinblueSib
 * @package Sendinblue\Sendinblue\Model
 */
class SendinblueSib extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $_dir;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManagerInterface;
    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    protected $_getTb;
    /**
     * @var \Sendinblue\Sendinblue\Model\SibClientFactory
     */
    protected $sibClientFactory;
    /**
     * @var CustomerAddressRepository
     */
    protected $customerAddressRepository;
    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;
    /**
     * @var ConfigHelper
     */
    protected $configHelper;
    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;
    /**
     * @var BackendAuthSession
     */
    protected $backendAuthSession;
    /**
     * @var \Magento\User\Model\User|null
     */
    protected $currentAdminUser = null;
    /**
     * @var MessageManager
     */
    protected $messageManager;
    /**
     * @var \Sendinblue\Sendinblue\Model\SibClient|null
     */
    protected $sibClient = null;
    /**
     * @var NewsletterSubscriberCollectionFactory
     */
    protected $newsletterSubscriberCollectionFactory;
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * SendinblueSib constructor.
     * @param \Magento\Framework\Filesystem\DirectoryList $dir
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $getTb
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param BackendAuthSession $backendAuthSession
     * @param SibClientFactory $sibClientFactory
     * @param CustomerAddressRepository $customerAddressRepository
     * @param CustomerRepository $customerRepository
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param ConfigHelper $configHelper
     * @param MessageManager $messageManager
     * @param NewsletterSubscriberCollectionFactory $newsletterSubscriberCollectionFactory
     */
    public function __construct(
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Setup\ModuleDataSetupInterface $getTb,
        CustomerCollectionFactory $customerCollectionFactory,
        BackendAuthSession $backendAuthSession,
        SibClientFactory $sibClientFactory,
        CustomerAddressRepository $customerAddressRepository,
        CustomerRepository $customerRepository,
        OrderCollectionFactory $orderCollectionFactory,
        ConfigHelper $configHelper,
        MessageManager $messageManager,
        NewsletterSubscriberCollectionFactory $newsletterSubscriberCollectionFactory
    )
    {
        $this->_dir = $dir;
        $this->_resource = $resource;
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->_getTb = $getTb;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->backendAuthSession = $backendAuthSession;
        $this->sibClientFactory = $sibClientFactory;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->configHelper = $configHelper;
        $this->customerRepository = $customerRepository;
        $this->messageManager = $messageManager;
        $this->newsletterSubscriberCollectionFactory = $newsletterSubscriberCollectionFactory;
    }

    /**
     * @return mixed
     */
    public function getCurrentUser()
    {
        if ($this->currentAdminUser === null) {
            $this->currentAdminUser = $this->backendAuthSession->getUser();
        }
        return $this->currentAdminUser;
    }

    /**
     * @param $key
     * @throws \SendinBlue\Client\ApiException
     */
    public function createFolderName($key)
    {
        $result = $this->checkFolderList($key);
        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient($key);

        if ($result == false) {
            $response = $sibClient->createFolder(array("name" => "magento"));
            if (SibClient::RESPONSE_CODE_CREATED == $sibClient->getLastResponseCode()) {
                $this->createNewList($key, $response["id"]);
            }
        } else {
            $this->createNewList($key, $result["folder"]["id"], (empty($result["list"]) ? false : $result["list"]["name"]));
        }
    }

    /**
     * Create a list by the name "Magento[date with dmY format]" on user's Sendinblue account.
     *
     * @param $key
     * @param $folder
     * @param bool $existList
     * @throws \SendinBlue\Client\ApiException
     */
    public function createNewList($key, $folder, $existList = false)
    {
        $list = 'magento';
        if ($existList) {
            $list = 'magento' . date('dmY');
        }
        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient($key);
        $response = $sibClient->createList(array("name" => $list, "folderId" => $folder));
        if (SibClient::RESPONSE_CODE_CREATED == $sibClient->getLastResponseCode()) {
            $this->configHelper->updateData('selected_list_data', $response['id']);
        }
    }

    /**
     * Check api status
     *
     * @param $key
     * @return array|bool
     * @throws \SendinBlue\Client\ApiException
     */
    public function checkApikey($key)
    {
        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient($key);
        /**
         * @var \SendinBlue\Client\Model\GetAccount $keyResponse
         */
        $keyResponse = $sibClient->getAccount();
        if (SibClient::RESPONSE_CODE_OK == $sibClient->getLastResponseCode()) {
            $resp = $this->pluginDateLangConfig($keyResponse);
            return $resp;
        }
        return false;
    }

    /**
     * Set date and language
     *
     * @param \SendinBlue\Client\Model\GetAccount $account
     * @return array
     */
    public function pluginDateLangConfig($account)
    {
        $lang = 'en';
        $dateFormat = 'dd-mm-yyyy';
        if ("france" == strtolower($account->getAddress()->getCountry())) {
            $dateFormat = 'mm-dd-yyyy';
            $lang = 'fr';
        }
        $this->updateDbData('sendin_config_lang', $lang);
        $this->updateDbData('sendin_date_format', $dateFormat);
        return array("lang" => $lang, "date_format" => $dateFormat);
    }

    /**
     * Fetches all the list of the user from the Sendinblue platform.
     *
     * @return array
     * @throws \SendinBlue\Client\ApiException
     */
    public function getResultListValue()
    {
        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient();
        $response = $sibClient->getAllLists();
        if (SibClient::RESPONSE_CODE_OK == $sibClient->getLastResponseCode()) {
            return $response;
        }
        return array("lists" => array(), "count" => 0);
    }

    /**
     * create object for access data from Sendinblue threw API V3 call.
     *
     * @param string $key
     * @return mixed
     */
    public function createSibClient($key = '')
    {
        if ($key === '') {
            $key = $this->getDbData('api_key_v3');
        }
        if ($this->sibClient === null || $this->sibClient->getApiKey() === null) {
            $this->sibClient = $this->sibClientFactory->create();
            $this->sibClient->setApiKey($key);
        }
        return $this->sibClient;
    }

    /**
     * Reset the configs to factory
     */
    public function resetDataBaseValue()
    {
        $this->configHelper->resetDefaultValues();
    }

    /**
     * Calculate Normal, Transactional, Calculated and Global attributes and their values
     * on Sendinblue platform. This is necessary to add subscriber's details.
     *
     * @param $key
     * @param string $config
     * @throws \SendinBlue\Client\ApiException
     */
    public function createAttributesName($key, $config = "")
    {
        $requiredAttr["normal"] = $this->configHelper->normalizeSubscriptionAttributes($config);
        $requiredAttr["calculated"] = $this->configHelper->getCalculatedSubscriptionAttributes();
        $requiredAttr["global"] = $this->configHelper->getGlobalSubscriptionAttributes();
        $requiredAttr["transactional"] = $this->configHelper->getTransactionalSubscriptionAttributes();

        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient($key);
        /**
         * @var \SendinBlue\Client\Model\GetAttributes $attr_list
         */
        $attrList = $sibClient->getAttributes();
        $attrExist = array();

        if ($attrList !== null && $attrList->getAttributes()) {
            foreach ($attrList->getAttributes() as $key => $value) {
                if (!isset($attrExist[$value->getCategory()])) {
                    $attrExist[$value->getCategory()] = array();
                }
                $attrExist[$value->getCategory()][] = $value;
            }
        }

        // Find which attribute is not created
        foreach ($requiredAttr as $key => $value) {
            if (isset($attrExist[$key])) {
                $tempName = array();
                foreach($attrExist[$key] as $attribute) {
                    $tempName[] = $attribute->getName();
                }
                foreach ($value as $key1 => $value1) {
                    if (in_array($value1["name"], $tempName)) {
                        unset($requiredAttr[$key][$key1]);
                    }
                }
            }
        }

        // To create normal attributes
        foreach ($requiredAttr["normal"] as $key => $value) {
            $sibClient->createAttribute("normal", $value["name"], array("type" => $value["type"]));
        }

        // To create transactional attributes
        foreach ($requiredAttr["transactional"] as $key => $value) {
            $sibClient->createAttribute("transactional", $value["name"], array("type" => $value["type"]));
        }

        // To create calculated attributes
        foreach ($requiredAttr["calculated"] as $key => $value) {
            $sibClient->createAttribute("calculated", $value["name"], array("value" => $value["value"]));
        }

        // To create global attributes
        foreach ($requiredAttr["global"] as $key => $value) {
            $sibClient->createAttribute("global", $value["name"], array("value" => $value["value"]));
        }
    }

    /**
     * Fetches all folders and all list within each folder of the user's Sendinblue
     * account and displays them to the user.
     *
     * @param $key
     * @return array|bool
     * @throws \SendinBlue\Client\ApiException
     */
    public function checkFolderList($key)
    {
        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient($key);
        $response = $sibClient->getFoldersAll();
        $listFolder = array();
        $folder = array();

        if (isset($response["folders"])) {
            foreach ($response["folders"] as $value) {
                if (strtolower($value["name"]) == 'magento') {
                    $folder = $value;
                }
            }
        }

        if (empty($folder)) {
            return false;
        }
        $listFolder["folder"] = $folder;
        $response2 = $sibClient->getAllLists($folder["id"]);

        if (empty($response2["lists"])) {
            return $listFolder;
        }

        foreach ($response2["lists"] as $list) {
            if ($list) {
                if (strtolower($list["name"]) == 'magento') {
                    $listFolder["list"] = $list;
                }
            }
        }
        return $listFolder;
    }

    /**
     * Send all the subscribers to Sendinblue for adding / updating purpose.
     *
     * @param $listId
     * @return int
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendAllMailIDToSendin($listId)
    {
        $emailValue = $this->getSubscribeCustomer();
        $mediaUrl = $this->_storeManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $this->updateDbData('import_old_user_status', -1);
        if ($emailValue > 0) {
            $this->updateDbData('import_old_user_status', 1);
            $userDataInformation = array();
            /**
             * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
             */
            $sibClient = $this->createSibClient();
            $userDataInformation['fileUrl'] = $mediaUrl . 'sendinblue_csv/' . $this->getDbData('sendin_csv_file_name') . '.csv';
            $userDataInformation['listIds'] = array(intval($listId));
            try {
                $sibClient->importUsers($userDataInformation);
            } catch (\Exception $e) {
                //@TODO do something... 500 error not managed by SDK
            }
            $this->updateDbData('selected_list_data', trim($listId));
            if (SibClient::RESPONSE_CODE_ACCEPTED == $sibClient->getLastResponseCode()) {
                $this->updateDbData('import_old_user_status', 0);
                return 0;
            }
            return 1;
        }
        return -1;
    }

    /**
     * Fetches all the subscribers and adds them to the Sendinblue databases
     *
     * @return int|void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSubscribeCustomer()
    {
        $customerAddressData = array();
        $attributesName = $this->configHelper->getDefaultSubscriptionAttributes();
        $collection = $this->customerCollectionFactory->create()->getItems();
        foreach ($collection as $customers) {
            $customerData = $customers->getData();
            $email = $customerData['email'];
            $customerId = $customerData['entity_id'];
            $billingId = $customers->getDefaultBilling();
            $customerAddress = array();
            if (!empty($billingId)) {
                $address = $this->customerAddressRepository->getById($billingId);
                $streetValue = implode(' ', $address->getStreet());

                $customerAddress['telephone'] = !empty($telephone = $address->getTelephone()) ? $telephone : '';
                $customerAddress['country_id'] = !empty($country = $address->getCountryId()) ? $country : '';
                $customerAddress['company'] = !empty($company = $address->getCompany()) ? $company : '';
                $customerAddress['street'] = !empty($streetValue) ? $streetValue : '';
                $customerAddress['postcode'] = !empty($postcode = $address->getPostcode()) ? $postcode : '';
                $customerAddress['region'] = !empty($region = $address->getRegion()->getRegionCode()) ? $region : '';
                $customerAddress['city'] = !empty($city = $address->getCity()) ? $city : '';
            }
            $customerAddress['client'] = $customerId > 0 ? 1 : 0;
            $customerAddressData[$email] = array_merge($customerData, $customerAddress);
        }

        $newsLetterData = array();
        $responseByMerge = array();
        $count = 0;
        /**
         * @var \Magento\Newsletter\Model\Subscriber[] $resultSubscriber
         */
        $resultSubscriber = $this->newsletterSubscriberCollectionFactory->create()
            ->addFieldToFilter('subscriber_status', 1)
            ->getItems();

        $stores = $this->_storeManagerInterface->getStores(true, false);
        $storeNames = array();
        foreach ($stores as $store) {
            $storeNames[$store->getId()] = $store->getName();
        }

        foreach ($resultSubscriber as $subsdata) {
            $subscriberEmail = $subsdata->getSubscriberEmail();

            if (!empty($customerAddressData[$subscriberEmail])) {
                $customerAddressData[$subscriberEmail]['email'] = $subscriberEmail;
                $responseByMerge[$count] = $this->mergeSubscriptionData($attributesName, $customerAddressData[$subscriberEmail], $subscriberEmail);
            } else {
                $storeId = $subsdata->getStoreId();
                $newsLetterData['client'] = $subsdata->getCustomerId() > 0 ? 1 : 0;
                $responseByMerge[$count] = $this->mergeSubscriptionData($attributesName, $newsLetterData, $subscriberEmail);
                $responseByMerge[$count]['STORE_ID'] = $storeId;
                $responseByMerge[$count]['MAGENTO_LANG'] = isset($storeNames[$storeId]) ? $storeNames[$storeId] : '';
            }
            ++$count;
        }

        if (!is_dir($this->_dir->getPath('media') . '/sendinblue_csv')) {
            mkdir($this->_dir->getPath('media') . '/sendinblue_csv', 0777, true);
        }
        $fileName = 'ImportContacts-' . time();
        $this->updateDbData('sendin_csv_file_name', $fileName);
        $handle = fopen($this->_dir->getPath('media') . '/sendinblue_csv/' . $fileName . '.csv', 'w+');

        $headRow = array_keys($attributesName);
        array_splice($headRow, 0, 0, 'EMAIL');
        fwrite($handle, implode(';', $headRow) . "\n");

        foreach ($responseByMerge as $row) {
            if (!empty($row['COUNTRY_ID']) && !empty($row['SMS'])) {
                $countryId = $this->getCountryCode($row['COUNTRY_ID']);
                if (!empty($countryId)) {
                    $row['SMS'] = $this->checkMobileNumber($row['SMS'], $countryId);
                }
            }
            fwrite($handle, str_replace("\n", '', implode(';', $row)) . "\n");
        }
        fclose($handle);
        $totalValue = count($responseByMerge);

        return $totalValue;
    }

    /**
     * @param $customerId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomer($customerId)
    {
        return $this->customerRepository->getById($customerId);
    }

    /**
     * @param $customerEmail
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerByEmail($customerEmail)
    {
        $dataCust = $this->customerRepository->get($customerEmail);
        return $dataCust->getData();
    }

    /**
     * @param $one
     * @param $two
     * @param string $email
     * @return array
     */
    public function mergeSubscriptionData($one, $two, $email = "")
    {
        $emailData = $email ? array('EMAIL' => $email) : array();
        if (count($one) > 0) {
            foreach ($one as $k => $v) {
                $emailData[$k] = isset($two[$v]) ? str_replace(';', ',', $two[$v]) : '';
            }
        }
        return $emailData;
    }

    /**
     * @TODO create model and resource model (dedicated entity) for sendinblue_country_codes table instead of direct queries
     * Get getCountryCode from sendinblue_country table
     *
     * @param $countryids
     * @return mixed|string
     */
    public function getCountryCode($countryids)
    {
        $connection = $this->createDbConnection();
        $tblCountryIso = $this->tbWithPrefix('sendinblue_country_codes');
        $countryPrefixData = $connection->fetchAll('SELECT * FROM `' . $tblCountryIso . '` WHERE `iso_code` = ' . "'$countryids'");
        $countryPrefix = !empty($countryPrefixData[0]['country_prefix']) ? $countryPrefixData[0]['country_prefix'] : '';
        return $countryPrefix;
    }

    /**
     * Get final sms value after add and check country code
     *
     * @param $number
     * @param $callPrefix
     * @return string|string[]|null
     */
    public function checkMobileNumber($number, $callPrefix)
    {
        $number = preg_replace('/\s+/', '', $number);
        $charOne = substr($number, 0, 1);
        $charTwo = substr($number, 0, 2);

        if (preg_match('/^' . $callPrefix . '/', $number)) {
            return '00' . $number;
        } elseif ($charOne == '0' && $charTwo != '00') {
            if (preg_match('/^0' . $callPrefix . '/', $number)) {
                return '00' . substr($number, 1);
            } else {
                return '00' . $callPrefix . substr($number, 1);
            }
        } elseif ($charTwo == '00') {
            if (preg_match('/^00' . $callPrefix . '/', $number)) {
                return $number;
            } else {
                return '00' . $callPrefix . substr($number, 2);
            }
        } elseif ($charOne == '+') {
            if (preg_match('/^\+' . $callPrefix . '/', $number)) {
                return '00' . substr($number, 1);
            } else {
                return '00' . $callPrefix . substr($number, 1);
            }
        } elseif ($charOne != '0') {
            return '00' . $callPrefix . $number;
        }
    }

    /**
     * Get core config table value data,
     *
     * @param $val
     * @return mixed
     */
    public function getDbData($val)
    {
        return $this->configHelper->getData($val);
    }

    /**
     * Update core config table value and data
     *
     * @param $key
     * @param $value
     */
    public function updateDbData($key, $value)
    {
        $this->configHelper->updateData($key, $value);
    }

    /**
     * Get all template list id by sendinblue.
     *
     * @return array
     * @throws \SendinBlue\Client\ApiException
     */
    public function templateDisplay()
    {
        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient();
        $response = $sibClient->getAllEmailTemplates();
        if (SibClient::RESPONSE_CODE_OK == $sibClient->getLastResponseCode()) {
            return $response;
        }
        return array("templates" => array(), "count" => 0);
    }

    /**
     * Show  SMS  credit from Sendinblue.
     *
     * @return int
     * @throws \SendinBlue\Client\ApiException
     */
    public function getSmsCredit()
    {
        if ($this->configHelper->isServiceActive()) {
            /**
             * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
             */
            $sibClient = $this->createSibClient();
            /**
             * @var \SendinBlue\Client\Model\GetAccount $dataResp
             */
            $dataResp = $sibClient->getAccount();
            if (SibClient::RESPONSE_CODE_OK == $sibClient->getLastResponseCode()) {
                foreach ($dataResp->getPlan() as $accountVal) {
                    if ($accountVal->getType() == 'sms') {
                        return $accountVal->getCredits();
                    }
                }
            }
        }
        return 0;
    }

    /**
     * Update sender name (from name and from email) from Sendinblue for email service.
     */
    public function updateSender()
    {
        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient();
        $response = $sibClient->getSenders();
        if (SibClient::RESPONSE_CODE_OK == $sibClient->getLastResponseCode()) {
            $senders = array('id' => $response->getSenders()[0]->getId(), 'from_name' => $response->getSenders()[0]->getName(), 'from_email' => $response->getSenders()[0]->getEmail());
            $this->updateDbData('sendin_sender_value', json_encode($senders));
        }
    }

    /**
     * Get all folder list and id from SIB and display in page.
     *
     * @return array|bool
     * @throws \SendinBlue\Client\ApiException
     */
    public function checkFolderListDoubleoptin()
    {
        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient();
        $dataApi = array("offset" => 1,
            "limit" => 50
        );
        $folderResp = $sibClient->getFolders($dataApi);
        $sArray = array();
        $returnVal = false;
        if (!empty($folderResp['folders'])) {
            foreach ($folderResp['folders'] as $value) {
                if (strtolower($value['name']) == 'form') {
                    $listResp = $sibClient->getAllLists($value['id']);
                    if (!empty($listResp['lists'])) {
                        foreach ($listResp['lists'] as $val) {
                            if ($val['name'] == 'Temp - DOUBLE OPTIN') {
                                $sArray['optin_id'] = $val['id'];
                            }
                        }
                    }
                }
            }
            if (count($sArray) > 0) {
                $returnVal = $sArray;
            } else {
                $returnVal = false;
            }
        }
        return $returnVal;
    }

    /**
     * Fetches the SMTP and order tracking details
     */
    public function trackingSmtp()
    {
        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient();
        $smtpDetails = $sibClient->getAccount();
        if (SibClient::RESPONSE_CODE_OK == $sibClient->getLastResponseCode()) {
            return $smtpDetails;
        }
        return false;
    }

    /**
     * Delete sendiblue SMTP entry
     */
    public function resetSmtpDetail()
    {
        $this->configHelper->resetSmtpDetail();
    }

    /**
     * Import old order history
     */
    public function importOrderhistory()
    {
        $dateValue = !empty($this->getDbData('sendin_date_format')) ? $this->getDbData('sendin_date_format') : 'mm-dd-yyyy';
        if (!is_dir($this->_dir->getPath('media') . '/sendinblue_csv')) {
            mkdir($this->_dir->getPath('media') . '/sendinblue_csv', 0777, true);
        }

        $handle = fopen($this->_dir->getPath('media') . '/sendinblue_csv/ImportOldOrdersToSendinblue.csv', 'w+');
        fwrite($handle, 'EMAIL;ORDER_ID;ORDER_PRICE;ORDER_DATE' . PHP_EOL);
        $collection = $this->customerCollectionFactory->create()->getItems();
        foreach ($collection as $customers) {
            $customerData = $customers->getData();
            $email = $customerData['email'];
            $customerId = $customerData['entity_id'];

            /**
             * @var \Magento\Newsletter\Model\Subscriber $resultSubscriber
             */
            $newsletterSubscriberCheck = $this->newsletterSubscriberCollectionFactory->create()
                ->addFieldToFilter('subscriber_email', $email)
                ->addFieldToFilter('subscriber_status', 1)
                ->getFirstItem();

            if ($newsletterSubscriberCheck->getId()) {
                $orders = $this->orderCollectionFactory->create()
                    ->addAttributeToFilter('customer_id', $customerId)
                    ->getItems();
                foreach ($orders as $orderDatamodel) {
                    $orderData = $orderDatamodel->getData();
                    $orderID = $orderData['increment_id'];
                    $orderPrice = $orderData['grand_total'];
                    $dateAdded = $orderData['created_at'];
                    if ($dateValue == 'dd-mm-yyyy') {
                        $orderDate = date('d-m-Y', strtotime($dateAdded));
                    } else {
                        $orderDate = date('m-d-Y', strtotime($dateAdded));
                    }
                    $historyData = array();
                    $historyData[] = array($email, $orderID, $orderPrice, $orderDate);
                    foreach ($historyData as $line) {
                        fputcsv($handle, $line, ';');
                    }
                }
            }
        }

        fclose($handle);
        $this->updateDbData('order_import_status', 1);
        $listId = $this->getDbData('selected_list_data');
        $userDataInformation = array();
        $listIdVal = array_map('intval', explode('|', $listId));
        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient();
        $baseUrl = $this->_storeManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $userDataInformation['fileUrl'] = $baseUrl . 'sendinblue_csv/ImportOldOrdersToSendinblue.csv';
        $userDataInformation['listIds'] = $listIdVal;
        try {
            $sibClient->importUsers($userDataInformation);
        } catch (\Exception $e) {
            //500 error (file not found maybe)
        }
        if (SibClient::RESPONSE_CODE_ACCEPTED === $sibClient->getLastResponseCode()) {
            return 1;
        }
        $this->updateDbData('order_import_status', 0);
        return 0;
    }

    /**
     * Test sms for order confirmation.
     *
     * @param $sender
     * @param $message
     * @param $number
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SendinBlue\Client\ApiException
     * @throws \Zend_Mail_Exception
     */
    public function sendOrderTestSms($sender, $message, $number)
    {
        if (!empty($number)) {
            $adminData = $this->getCurrentUser();
            $firstname = $adminData->getData('firstname');
            $lastname = $adminData->getData('lastname');
            $characters = '1234567890';
            $referenceNumber = '';
            for ($i = 0; $i < 9; $i++) {
                $referenceNumber .= $characters[rand(0, strlen($characters) - 1)];
            }
            $localeCode = $adminData['interface_locale'];
            $orderDateFormat = ($localeCode == 'fr_FR') ? date('d/m/Y') : date('m/d/Y');
            $orderprice = rand(10, 1000);
            $currency = $this->_storeManagerInterface->getStore()->getCurrentCurrencyCode();
            $totalPay = $orderprice . '.00' . ' ' . $currency;
            $firstName = str_replace('{first_name}', $firstname, $message);
            $lastName = str_replace('{last_name}', $lastname . "\r\n", $firstName);
            $procuctPrice = str_replace('{order_price}', $totalPay, $lastName);
            $orderDate = str_replace('{order_date}', $orderDateFormat . "\r\n", $procuctPrice);
            $msgbody = str_replace('{order_reference}', $referenceNumber, $orderDate);
            $smsData = array();
            $smsData['to'] = $number;
            $smsData['from'] = !empty($sender) ? $sender : '';
            $smsData['text'] = $msgbody;
            $smsData['type'] = 'transactional';
            $responseValue = $this->sendSmsApi($smsData);
            if ($responseValue == 'success') {
                return 'OK';
            } else {
                return 'KO';
            }
        }
    }

    /**
     * Test sms for order confirmation.
     *
     * @param $sender
     * @param $message
     * @param $number
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SendinBlue\Client\ApiException
     * @throws \Zend_Mail_Exception
     */
    public function sendShippedTestSms($sender, $message, $number)
    {
        if (!empty($number)) {
            $adminData = $this->getCurrentUser();
            $firstname = $adminData['firstname'];
            $lastname = $adminData['lastname'];
            $characters = '1234567890';
            $referenceNumber = '';
            for ($i = 0; $i < 9; $i++) {
                $referenceNumber .= $characters[rand(0, strlen($characters) - 1)];
            }
            $localeCode = $adminData['interface_locale'];
            $orderDateFormat = ($localeCode == 'fr_FR') ? date('d/m/Y') : date('m/d/Y');
            $orderprice = rand(10, 1000);
            $currency = $this->_storeManagerInterface->getStore()->getCurrentCurrencyCode();
            $totalPay = $orderprice . '.00' . ' ' . $currency;
            $firstName = str_replace('{first_name}', $firstname, $message);
            $lastName = str_replace('{last_name}', $lastname . "\r\n", $firstName);
            $procuctPrice = str_replace('{order_price}', $totalPay, $lastName);
            $orderDate = str_replace('{order_date}', $orderDateFormat . "\r\n", $procuctPrice);
            $msgbody = str_replace('{order_reference}', $referenceNumber, $orderDate);
            $smsData = array();
            $smsData['to'] = $number;
            $smsData['from'] = !empty($sender) ? $sender : '';
            $smsData['text'] = $msgbody;
            $smsData['type'] = 'transactional';
            $responseValue = $this->sendSmsApi($smsData);
            if ($responseValue == 'success') {
                return 'OK';
            } else {
                return 'KO';
            }
        }
    }

    /**
     * Test sms for order confirmation.
     *
     * @param $sender
     * @param $message
     * @param $number
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SendinBlue\Client\ApiException
     * @throws \Zend_Mail_Exception
     */
    public function sendCampaignTestSms($sender, $message, $number)
    {

        if (!empty($number)) {
            $adminData = $this->getCurrentUser();
            $firstname = $adminData['firstname'];
            $lastname = $adminData['lastname'];
            $firstName = str_replace('{first_name}', $firstname, $message);
            $msgbody = str_replace('{last_name}', $lastname . "\r\n", $firstName);
            $smsData = array();
            $smsData['to'] = $number;
            $smsData['from'] = !empty($sender) ? $sender : '';
            $smsData['text'] = $msgbody;
            $smsData['type'] = 'transactional';
            $responseValue = $this->sendSmsApi($smsData);
            if ($responseValue == 'success') {
                return 'OK';
            } else {
                return 'KO';
            }
        }
    }

    /**
     * Send SMS from Sendinblue.
     * Get Param sender and msg fields.
     *
     * @param $dataSms
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SendinBlue\Client\ApiException
     * @throws \Zend_Mail_Exception
     */
    public function sendSmsApi($dataSms)
    {
        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient();
        $dataFinal = array("recipient" => trim($dataSms['to']),
            "sender" => trim($dataSms['from']),
            "content" => trim($dataSms['text']),
            "type" => trim($dataSms['type']),
            "source" => "api",
            "plugin" => "sendinblue-magento-plugin"
        );
        $dataResp = $sibClient->sendSms($dataFinal);
        if (SibClient::RESPONSE_CODE_CREATED === $sibClient->getLastResponseCode()) {
            $notifyLimit = $this->getDbData('notify_value');
            $emailSendStatus = $this->getDbData('notify_email_send');
            if (!empty($notifyLimit) && $dataResp->getRemainingCredits() <= $notifyLimit && $emailSendStatus == 0) {
                $smtpResult = $this->getDbData('relay_data_status');
                if ($smtpResult == 'enabled') {
                    $this->sendNotifySms($dataResp->getRemainingCredits());
                    $this->updateDbData('notify_email_send', 1);
                }
            }
            return 'success';
        } else {
            return 'fail';
        }
    }

    /**
     * This method is called when the user sets the Campaign single Choic eCampaign and hits the submit button.
     *
     * @param $post
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SendinBlue\Client\ApiException
     * @throws \Zend_Mail_Exception
     */
    public function singleChoiceCampaign($post)
    {
        $senderCampaign = $post['sender_campaign'];
        $senderCampaignNumber = $post['singlechoice'];
        $senderCampaignMessage = $post['sender_campaign_message'];

        if (!empty($senderCampaign) && !empty($senderCampaignNumber) && !empty($senderCampaignMessage)) {
            $arr = array();
            $arr['to'] = $senderCampaignNumber;
            $arr['from'] = $senderCampaign;
            $arr['text'] = $senderCampaignMessage;
            $arr['type'] = "transactional";
            $result = $this->sendSmsApi($arr);
            return $result;
        }
        return 'fail';
    }

    /**
     * This method is called when the user sets the Campaign multiple Choice and hits subscribed user the submit button.
     *
     * @param $post
     * @return string
     * @throws \SendinBlue\Client\ApiException
     */
    public function multipleChoiceSubCampaign($post)
    {
        $senderCampaign = $post['sender_campaign'];
        $senderCampaignMessage = $post['sender_campaign_message'];
        $scheduleMonth = $post['sib_datetimepicker'];
        $scheduleHour = $post['hour'];
        $scheduleMinute = $post['minute'];

        if ($scheduleHour < 10) {
            $scheduleHour = '0' . $scheduleHour;
        }
        if ($scheduleMinute < 10) {
            $scheduleMinute = '0' . $scheduleMinute;
        }

        $scheduleTime = $scheduleMonth . ' ' . $scheduleHour . ':' . $scheduleMinute . ':00';

        $currentTime = date('Y-m-d H:i:s', time() + 300);
        $currenttm = strtotime($currentTime);
        $scheduletm = strtotime($scheduleTime);

        if (!empty($scheduleTime) && ($scheduletm < $currenttm)) {
            return 'failled';
        }
        if (!empty($senderCampaign) && !empty($senderCampaignMessage)) {
            $campName = 'SMS_' . date('Ymd');
            $localeLangId = $this->getDbData('sendin_config_lang');

            if (strtolower($localeLangId) == 'fr') {
                $firstName = '{PRENOM}';
                $lastName = '{NOM}';
            } else {
                $firstName = '{NAME}';
                $lastName = '{SURNAME}';
            }

            $fname = str_replace('{first_name}', $firstName, $senderCampaignMessage);
            $content = str_replace('{last_name}', $lastName . "\r\n", $fname);
            $listId = $this->getDbData('selected_list_data');

            $listValue = array_map('intval', explode('|', $listId));

            /**
             * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
             */
            $sibClient = $this->createSibClient();
            $data = array("name" => $campName,
                "sender" => $senderCampaign,
                "content" => $content,
                "recipients" => array("listIds" => $listValue),
                "scheduledAt" => $scheduleTime
            );
            $sibClient->createSmsCampaign($data);

            if (SibClient::RESPONSE_CODE_CREATED === $sibClient->getLastResponseCode()) {
                return 'success';
            } else {
                return 'fail';
            }
        }
        return 'fail';
    }

    /**
     * This method is called when the user sets the Campaign multiple Choic eCampaign and hits the submit button.
     *
     * @param $post
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SendinBlue\Client\ApiException
     * @throws \Zend_Mail_Exception
     */
    public function multipleChoiceCampaign($post)
    {
        $senderCampaign = $post['sender_campaign'];
        $senderCampaignMessage = $post['sender_campaign_message'];
        $smsCredit = $this->getSmsCredit();
        if (!empty($senderCampaign) && !empty($senderCampaignMessage) && $smsCredit >= 1) {
            $arr = array();
            $mobile = '';
            $arr['from'] = $senderCampaign;

            $customerCollection = $this->customerCollectionFactory->create()->getItems();
            foreach ($customerCollection as $customerData) {
                $billingId = $customerData->getDefaultBilling();
                if (!empty($billingId)) {
                    $address = $this->customerAddressRepository->getById($billingId);
                    $smsValue = $address->getTelephone();
                    $countryId = $address->getCountryId();
                    $firstName = $address->getFirstname();
                    $lastName = $address->getLastname();
                    $countryPrefix = $this->getCountryCode($countryId);
                    if (!empty($smsValue) && !empty($countryPrefix)) {
                        $mobile = $this->checkMobileNumber($smsValue, $countryPrefix);
                    }
                    $fname = str_replace('{first_name}', ucfirst($firstName), $senderCampaignMessage);
                    $lname = str_replace('{last_name}', $lastName . "\r\n", $fname);
                    $arr['text'] = $lname;
                    $arr['to'] = $mobile;
                    $arr['type'] = "transactional";
                    $this->sendSmsApi($arr);
                }
            }
            return 'success';
        }
    }

    /**
     * Send single user in Sendinblue.
     */
    public function subscribeByruntime($userEmail, $updateDataInSib)
    {
        if ($this->syncSetting() != 1) {
            return false;
        }

        $sendinConfirmType = $this->getDbData('confirm_type');
        if ($sendinConfirmType === 'doubleoptin') {
            $listId = $this->getDbData('optin_list_id');
            $updateDataInSib['DOUBLE_OPT-IN'] = '2';
        } else {
            $listId = $this->getDbData('selected_list_data');
        }

        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient();

        $data = array("email" => $userEmail,
            "attributes" => $updateDataInSib,
            "emailBlacklisted" => false,
            "internalUserHistory" => array("action" => "SUBSCRIBE_BY_PLUGIN", "id" => 1, "name" => "magento2"),
            "updateEnabled" => true,
            "listIds" => array_map('intval', explode('|', $listId))
        );
        $sibClient->createUser($data);
    }

    /**
     * Checks whether the Sendinblue API key and the Sendinblue subscription form is enabled
     * and returns the true|false accordingly.
     */
    public function syncSetting()
    {
        return $this->configHelper->syncSetting();
    }

    /**
     * Unsibscribe single user in Sendinblue.
     *
     * @param $userEmail
     * @return bool
     * @throws \SendinBlue\Client\ApiException
     */
    public function unsubscribeByruntime($userEmail)
    {
        if ($this->syncSetting() != 1) {
            return false;
        }

        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient();
        $sibClient->updateUser($userEmail, array('emailBlacklisted' => true, "smsBlacklisted" => true));
    }

    /**
     * Check newsletter email status.
     *
     * @param $email
     * @return mixed|string
     */
    public function checkNlStatus($email)
    {
        /**
         * @var \Magento\Newsletter\Model\Subscriber $resultSubscriber
         */
        $resultSubscriber = $this->newsletterSubscriberCollectionFactory->create()
            ->addFieldToSelect('subscriber_status')
            ->addFieldToFilter('subscriber_email', $email)
            ->getFirstItem();

        return ( $resultSubscriber->getId() ? $resultSubscriber->getSubscriberStatus() : '');
    }

    /**
     * Create db connection.
     */
    public function createDbConnection()
    {
        return $connection = $this->_resource->getConnection(
            \Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION
        );
    }

    /**
     * Send sendinblue configuration mail for testing purpose.
     *
     * @param $userEmail
     * @param $title
     * @param $tempName
     * @param $paramVal
     * @return array
     * @throws \Zend_Mail_Exception
     */
    public function smtpSendMail($userEmail, $title, $tempName, $paramVal)
    {
        $result = [];
        $result['status'] = false;
        $relayData = $this->getDbData('relay_data_status');
        if ($relayData == 'enabled' && !empty($userEmail)) {
            $config = [];
            $host = $this->getDbData('smtp_host');
            $config['tls'] = $this->getDbData('smtp_tls');
            $config['port'] = $this->getDbData('smtp_port');
            $config['auth'] = $this->getDbData('smtp_authentication');
            $config['username'] = $this->getDbData('smtp_username');
            $config['password'] = $this->getDbData('smtp_password');

            $transport = new \Zend_Mail_Transport_Smtp($host, $config);

            $mail = new \Zend_Mail();
            $sender = $this->getDbData('sendin_sender_value');
            $dataSender = json_decode($sender);
            $senderName = !empty($dataSender->from_name) ? $dataSender->from_name : 'Sendinblue';
            $senderEmail = !empty($dataSender->from_email) ? $dataSender->from_email : 'contact@sendinblue.com';

            $mail->setFrom($senderEmail, $senderName);

            $mail->addTo($userEmail);
            $mail->setSubject($title);
            $lang = $this->getDbData('sendin_config_lang');
            if ($tempName == 'doubleoptin_temp') {
                $bodyContent = $this->configHelper->getOptinDefaultTemplate(strtolower($lang));
            } else if ($tempName == 'sendin_notification') {
                $bodyContent = $this->configHelper->getSendinDefaultTemplate(strtolower($lang));
            } else if ($tempName == 'sendinsmtp_conf') {
                $bodyContent = $this->configHelper->getSmtpDefaultTemplate(strtolower($lang));
            }

            if (!empty($paramVal)) {
                foreach ($paramVal as $key => $replaceVal) {
                    $bodyContent = str_replace($key, $replaceVal, $bodyContent);
                }
            }
            $mail->setBodyHtml($bodyContent);
            try {
                $mail->send($transport);
                $result['status'] = true;
                $result['content'] = __('Sent successfully! Please check your email box.');
            } catch (\Exception $e) {
                $result['content'] = $e->getMessage();
            }
        } else {
            $result['content'] = __('Test Error');
        }
        return $result;
    }

    /**
     * @param $remainingSms
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Mail_Exception
     */
    public function sendNotifySms($remainingSms)
    {
        $notifyEmail = $this->getDbData('notify_email');
        $tempName = 'sendin_notification';
        $title = __('[Sendinblue] Notification : Credits SMS');
        $siteName = $this->_storeManagerInterface->getStore()->getName();
        $paramVal = array('{present_credit}' => $remainingSms, '{site_name}' => $siteName);
        $this->smtpSendMail($notifyEmail, $title, $tempName, $paramVal);
    }

    /**
     * Send template email by sendinblue for newsletter subscriber user.
     *
     * @param $to
     * @return array|bool|\SendinBlue\Client\Model\CreateSmtpEmail
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SendinBlue\Client\ApiException
     * @throws \Zend_Mail_Exception
     */
    public function sendWsTemplateMail($to)
    {
        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient();

        $sendinConfirmType = $this->getDbData('confirm_type');
        if (empty($sendinConfirmType) || $sendinConfirmType == 'nocon') {
            return false;
        }

        if ($sendinConfirmType == 'simplemail') {
            $tempIdValue = $this->getDbData('template_id');
            $templateId = !empty($tempIdValue) ? $tempIdValue : '';
        }

        if ($sendinConfirmType == 'doubleoptin') {

            $path = $this->_storeManagerInterface->getStore()->getBaseUrl() . 'sendinblue';
            $siteName = $this->_storeManagerInterface->getStore()->getName();
            $pathResp = $path . '?value=' . base64_encode($to);

            $title = __('Please confirm your subscription');

            $paramVal = array('{double_optin}' => $pathResp);
            $tempName = 'doubleoptin_temp';
            $sender = $this->getDbData('sendin_sender_value');
            $dataSender = json_decode($sender);
            $senderName = !empty($dataSender->from_name) ? $dataSender->from_name : 'Sendinblue';
            $senderEmail = !empty($dataSender->from_email) ? $dataSender->from_email : 'no-reply@sendinblue.com';

            $doubleOptinTempId = $this->getDbData('doubleoptin_template_id');

            if (intval($doubleOptinTempId) > 0) {
                $response = $sibClient->getTemplateById($doubleOptinTempId);
                if (SibClient::RESPONSE_CODE_OK == $sibClient->getLastResponseCode()) {
                    $htmlContent = $response->getHtmlContent();
                    if (trim($response['subject']) != '') {
                        $subject = trim($response['subject']);
                    }
                    if (($response->getSender()->getName() != '[DEFAULT_FROM_NAME]') &&
                        ($response->getSender()->getEmail() != '[DEFAULT_FROM_EMAIL]') &&
                        ($response->getSender()->getEmail() != '')) {
                        $senderName = $response->getSender()->getName();
                        $senderEmail = $response->getSender()->getEmail();
                    }
                    $transactionalTags = $response->getSender()->getName();
                }
            } else {
                return $this->smtpSendMail($to, $title, $tempName, $paramVal);
            }

            $to = array(array("email" => $to));
            $from = array("email" => $senderEmail, "name" => $senderName);
            $searchValue = "({{\s*doubleoptin\s*}})";

            $htmlContent = str_replace('{title}', $subject, $htmlContent);
            $htmlContent = str_replace('https://[DOUBLEOPTIN]', '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('http://[DOUBLEOPTIN]', '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('https://{{doubleoptin}}', '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('http://{{doubleoptin}}', '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('https://{{ doubleoptin }}', '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('http://{{ doubleoptin }}', '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('[DOUBLEOPTIN]', '{subscribe_url}', $htmlContent);
            $htmlContent = preg_replace($searchValue, '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('{site_name}', $siteName, $htmlContent);
            $htmlContent = str_replace('{unsubscribe_url}', $pathResp, $htmlContent);
            $htmlContent = str_replace('{subscribe_url}', $pathResp, $htmlContent);

            $headers = array("Content-Type" => "text/html;charset=iso-8859-1", "X-Mailin-tag" => $transactionalTags);
            $data = array(
                "to" => $to,
                "sender" => $from,
                "subject" => $subject,
                "htmlContent" => $htmlContent,
                "headers" => $headers,
            );
            return $sibClient->sendEmail($data);
        }

        // should be the campaign id of template created on mailin. Please remember this template should be active than only it will be sent, otherwise it will return error.
        $data = array();

        $data = array("templateId" => intval($templateId),
            "to" => array(array("email" => $to))
        );
        $sibClient->sendTransactionalTemplate($data);
    }

    /**
     * After Click on Option link and if admin checked confirmaition e-mail send
     * to user.
     *
     * @param $customerEmail
     * @param $finalId
     * @throws \SendinBlue\Client\ApiException
     */
    public function sendOptinConfirmMailResponce($customerEmail, $finalId)
    {
        /**
         * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
         */
        $sibClient = $this->createSibClient();
        $data = array("templateId" => intval($finalId),
            "to" => array(array("email" => $customerEmail))
        );
        $sibClient->sendTransactionalTemplate($data);
    }

    /**
     * check port 587 open or not, for using Sendinblue smtp service.
     */
    public function checkPortStatus()
    {
        try {
            $relay_port_status = fsockopen(‘smtp - relay . sendinblue . com’, 587);
            if (!$relay_port_status) {
                return 0;
            }
        } catch (\Exception $e) {
            return 1;
        }
    }

    /**
     * Get table prefix (should be removed using collections instead of direct queries)
     * @param $tableName
     * @return string
     */
    public function tbWithPrefix($tableName)
    {
        return $this->_getTb->getTable($tableName);
    }
}

