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

/**
 * Class CustomerAddressUpdate
 * @package Sendinblue\Sendinblue\Observer
 */
class CustomerAddressUpdate implements ObserverInterface
{

    /**
     * @var SendinblueSib
     */
    protected $sendinblueSib;

    /**
     * SibObserver constructor.
     * @param SendinblueSib $sendinblueSib
     */
    public function __construct(
        SendinblueSib $sendinblueSib
    )
    {
        $this->sendinblueSib = $sendinblueSib;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $model = $this->sendinblueSib;
        $updateDataInSib = array();
        $address = $observer->getCustomerAddress();
        $customer = $address->getCustomer();
        $billing = $address->getIsDefaultBilling();
        $shipping = $address->getIsDefaultShipping();
        $status = !empty($billing) ? $billing : $shipping;
        $email= $customer->getEmail();
        $NlStatus = $model->checkNlStatus($email);
        $sibStatus = $model->syncSetting();
        if ($status == 1 && $NlStatus == 1 && $sibStatus == 1) {
            $street = $address->getStreet();
            $streetValue = '';
            foreach ($street as $streetData) {
                $streetValue.= $streetData.' ';
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
            $model->subscribeByruntime($email, $updateDataInSib);
        }
    }
}
