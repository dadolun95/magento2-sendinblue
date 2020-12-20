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
     * SibNlObserver constructor.
     * @param SendinblueSib $sendinblueSib
     * @param CustomerAddressRepository $customerAddressRepository
     */
    public function __construct(
        SendinblueSib $sendinblueSib,
        CustomerAddressRepository $customerAddressRepository
    )
    {
        $this->sendinblueSib = $sendinblueSib;
        $this->customerAddressRepository = $customerAddressRepository;
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
        $subscriberData = $observer->getEvent()->getSubscriber()->getData();
        $email = $subscriberData['subscriber_email'];
        $NlStatus = $subscriberData['subscriber_status'];
        $sibStatus = $model->syncSetting();
        if ($sibStatus == 1) {
            if (!empty($subscriberData['customer_id']) && $subscriberData['customer_id'] > 0 && $NlStatus == 1) {
                $customer = $model->getCustomer($subscriberData['customer_id']);
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
                    $storeId = $subscriberData['store_id'];
                    if (!empty($storeId)) {
                        $updateDataInSib['STORE_ID'] = $storeId;
                    }
                    $storeId = $subscriberData['store_id'];
                    $stores = $model->_storeManagerInterface->getStores(true, false);
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
