<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */
namespace Sendinblue\Sendinblue\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Sendinblue\Sendinblue\Model\SendinblueSib;
use Magento\Customer\Api\AddressRepositoryInterface as CustomerAddressRepository;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @TODO add logs for this observer
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
     * SibNlObserver constructor.
     * @param SendinblueSib $sendinblueSib
     * @param CustomerAddressRepository $customerAddressRepository
     */
    public function __construct(
        SendinblueSib $sendinblueSib,
        CustomerAddressRepository $customerAddressRepository,
        StoreManagerInterface $storeManager
    )
    {
        $this->sendinblueSib = $sendinblueSib;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->storeManager = $storeManager;
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
        $model = $this->sendinblueSib;
        $updateDataInSib = [];
        $subscriber = $observer->getEvent()->getSubscriber();
        $email = $subscriber->getSubscriberEmail();
        $NlStatus = $subscriber->getSubscriberStatus();
        $sibStatus = $model->syncSetting();
        if ($sibStatus == 1) {
            if ($subscriber->getCustomerId() && $subscriber->getCustomerId() > 0 && $NlStatus == 1) {
                $customer = $model->getCustomer($subscriber->getCustomerId());
                $billingId = !empty($customer['default_billing']) ? $customer['default_billing'] : '';
                $firstName = $customer['firstname'];
                $lastName = $customer['lastname'];
                $storeView = $customer['created_in'];
                $storeId = $customer['store_id'];
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
                    $updateDataInSib['REGION'] = !empty($address->getRegion()) ? $address->getRegion() : '';
                    $updateDataInSib['CITY'] = !empty($address->getCity()) ? $address->getCity() : '';
                }
                $model->subscribeByruntime($email, $updateDataInSib);
            } else {
                if ($NlStatus == 1) {
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
                    $model->unsubscribeByruntime($email);
                }
            }
        }
    }
}