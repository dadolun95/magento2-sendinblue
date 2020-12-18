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
 * Class SibObserver
 * @package Sendinblue\Sendinblue\Observer
 */
class SibObserver implements ObserverInterface
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Mail_Exception
     */
    public function execute(Observer $observer)
    {
        $model = $this->sendinblueSib;
        $customer = $observer->getEvent()->getData('customer');
        $customerId = $customer->getId();
        $email= $customer->getEmail();
        $NlStatus = $model->checkNlStatus($email);
        $apiKey = $model->getDbData('api_key');
        $sibStatus = $model->syncSetting();
        if ($NlStatus == 1 && $sibStatus == 1) {
            $firstName = $customer->getFirstName();
            $lastName = $customer->getLastName();
            $storeView = $customer->getCreatedIn();
            $storeId = $customer->getStoreId();
            $updateDataInSib = [];
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
            if (!empty($firstName)) {
                $updateDataInSib['CLIENT'] = 1;
            } else {
                $updateDataInSib['CLIENT'] = 0;
            }
            if (!empty($storeId)) {
                $updateDataInSib['STORE_ID'] = $storeId;
            }
            if (!empty($storeView)) {
                $updateDataInSib['MAGENTO_LANG'] = $storeView;
            }
            $model->subscribeByruntime($email, $updateDataInSib);
            $model->sendWsTemplateMail($email);
        }
    }
}
