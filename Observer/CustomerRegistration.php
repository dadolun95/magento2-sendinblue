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
use \Sendinblue\Sendinblue\Helper\DebugLogger;

/**
 * Class CustomerRegistration
 * @package Sendinblue\Sendinblue\Observer
 */
class CustomerRegistration implements ObserverInterface
{
    /**
     * @var SendinblueSib
     */
    protected $sendinblueSib;
    /**
     * @var DebugLogger
     */
    protected $debugLogger;

    /**
     * CustomerRegistration constructor.
     * @param SendinblueSib $sendinblueSib
     * @param DebugLogger $debugLogger
     */
    public function __construct(
        SendinblueSib $sendinblueSib,
        DebugLogger $debugLogger
    )
    {
        $this->sendinblueSib = $sendinblueSib;
        $this->debugLogger = $debugLogger;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SendinBlue\Client\ApiException
     * @throws \Zend_Mail_Exception
     */
    public function execute(Observer $observer)
    {
        $this->debugLogger->log(__('CustomerRegistration observer START'));
        $model = $this->sendinblueSib;
        $customer = $observer->getEvent()->getData('customer');
        $email = $customer->getEmail();
        $NlStatus = $model->checkNlStatus($email);
        $sibStatus = $model->syncSetting();
        $this->debugLogger->log(__('Try register user with email: %1', $email));
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
        } else {
            $this->debugLogger->log(__('Contact Sync is not enabled'));
        }
        $this->debugLogger->log(__('CustomerRegistration observer END'));
    }
}
