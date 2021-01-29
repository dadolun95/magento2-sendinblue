<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */
namespace Sendinblue\Sendinblue\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use \Sendinblue\Sendinblue\Model\SendinblueSib;
use Magento\Customer\Api\AddressRepositoryInterface as CustomerAddressRepository;
use Magento\Store\Model\StoreManagerInterface;
use \Sendinblue\Sendinblue\Helper\DebugLogger;

/**
 * Class NewsletterSubscription
 * @package Sendinblue\Sendinblue\Observer
 */
class NewsletterSubscription implements ObserverInterface
{

    /**
     * @var SendinblueSib
     */
    protected $sendinblueSib;

    /**
     * @var CustomerAddressRepository
     */
    protected $customerAddressRepository;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var DebugLogger
     */
    protected $debugLogger;

    /**
     * NewsletterSubscription constructor.
     * @param SendinblueSib $sendinblueSib
     * @param CustomerAddressRepository $customerAddressRepository
     * @param StoreManagerInterface $storeManager
     * @param DebugLogger $debugLogger
     */
    public function __construct(
        SendinblueSib $sendinblueSib,
        CustomerAddressRepository $customerAddressRepository,
        StoreManagerInterface $storeManager,
        DebugLogger $debugLogger
    )
    {
        $this->sendinblueSib = $sendinblueSib;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->storeManager = $storeManager;
        $this->debugLogger = $debugLogger;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SendinBlue\Client\ApiException
     * @throws \Zend_Mail_Exception
     */
    public function execute(Observer $observer)
    {
        $this->debugLogger->log(__('NewsletterSubscription observer START'));
        $model = $this->sendinblueSib;
        $updateDataInSib = [];
        $subscriber = $observer->getEvent()->getSubscriber();
        $email = $subscriber->getSubscriberEmail();
        $NlStatus = $subscriber->getSubscriberStatus();
        $sibStatus = $model->syncSetting();
        $this->debugLogger->log(__('Try update subscription for user with email: %1', $email));
        if ($sibStatus == 1) {
            if ($subscriber->getCustomerId() && $subscriber->getCustomerId() > 0 && $NlStatus == 1) {
                $this->debugLogger->log(__('Subscribe user by runtime'));
                /**
                 * @var \Magento\Customer\Api\Data\CustomerInterface $customer
                 */
                $customer = $model->getCustomer($subscriber->getCustomerId());
                $billingId = !empty($customer->getDefaultBilling()) ? $customer->getDefaultBilling() : '';
                $firstName = $customer->getFirstname();
                $lastName = $customer->getLastname();
                $storeView = $customer->getCreatedIn();
                $storeId = $customer->getStoreId();
                $localeLang = $model->getDbData('sendin_config_lang');
                if (!empty($firstName)) {
                    if ($localeLang == 'fr') {
                        $updateDataInSib['PRENOM'] = $firstName;
                    } else {
                        $updateDataInSib['NAME'] = $firstName;
                    }
                }
                if (!empty($lastName)) {
                    if ($localeLang == 'fr') {
                        $updateDataInSib['NOM'] = $lastName;
                    } else {
                        $updateDataInSib['SURNAME'] = $lastName;
                    }
                }

                $updateDataInSib['CLIENT'] = 1;

                if (!empty($storeId)) {
                    $updateDataInSib['STORE_ID'] = $storeId;
                }
                if (!empty($storeView)) {
                    $updateDataInSib['MAGENTO_LANG'] = $storeView;
                }

                if (!empty($billingId)) {
                    $address = $this->customerAddressRepository->getById($billingId);
                    $street = $address->getStreet();
                    $streetValue = '';
                    foreach ($street as $streetData) {
                        $streetValue .= $streetData . ' ';
                    }

                    $smsValue = !empty($address->getTelephone()) ? $address->getTelephone() : '';

                    $countryId = !empty($address->getCountryId()) ? $address->getCountryId() : '';
                    if (!empty($smsValue) && !empty($countryId)) {
                        $countryCode = $model->getCountryCode($countryId);
                        if (!empty($countryCode)) {
                            $updateDataInSib['SMS'] = $model->checkMobileNumber($smsValue, $countryCode);
                        }
                    }

                    $updateDataInSib['COMPANY'] = !empty($address->getCompany()) ? $address->getCompany() : '';
                    $updateDataInSib['COUNTRY_ID'] = !empty($address->getCountryId()) ? $address->getCountryId() : '';
                    $updateDataInSib['STREET'] = !empty($streetValue) ? $streetValue : '';
                    $updateDataInSib['POSTCODE'] = !empty($address->getPostcode()) ? $address->getPostcode() : '';
                    $updateDataInSib['REGION'] = !empty($address->getRegion()) ? $address->getRegion()->getRegionCode() : '';
                    $updateDataInSib['CITY'] = !empty($address->getCity()) ? $address->getCity() : '';
                }
                $model->subscribeByruntime($email, $updateDataInSib);
            } else {
                if ($NlStatus == 1) {
                    $this->debugLogger->log(__('Subscribe user by runtime'));
                    $updateDataInSib['CLIENT'] = 0;
                    $storeId = $subscriber->getStoreId();
                    if ($storeId) {
                        $updateDataInSib['STORE_ID'] = $storeId;
                    }
                    $stores = $this->storeManager->getStores(true, false);
                    foreach ($stores as $store) {
                        if ($store->getId() == $storeId) {
                            $storeView = $store->getName();
                        }
                    }
                    if (!empty($storeView)) {
                        $updateDataInSib['MAGENTO_LANG'] = $storeView;
                    }
                    $model->subscribeByruntime($email, $updateDataInSib);
                    $model->sendWsTemplateMail($email);
                } else {
                    $this->debugLogger->log(__('Unsubscribe user by runtime'));
                    $model->unsubscribeByruntime($email);
                }
            }
        } else {
            $this->debugLogger->log(__('Contact Sync is not enabled'));
        }
        $this->debugLogger->log(__('NewsletterSubscription observer END'));
    }
}
