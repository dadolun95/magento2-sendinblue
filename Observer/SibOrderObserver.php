<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */

namespace Sendinblue\Sendinblue\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Sendinblue\Sendinblue\Model\SendinblueSib;
use Magento\Customer\Model\Address as CustomerAddress;

/**
 * Class SibOrderObserver
 * @package Sendinblue\Sendinblue\Observer
 */
class SibOrderObserver implements ObserverInterface
{

    /**
     * @var SendinblueSib
     */
    protected $sendinblueSib;

    /**
     * @var OrderInterface
     */
    protected $order;

    /**
     * @var CustomerAddress
     */
    protected $customerAddress;

    /**
     * SibOrderObserver constructor.
     * @param SendinblueSib $sendinblueSib
     * @param OrderInterface $order
     * @param CustomerAddress $customerAddress
     */
    public function __construct(
        SendinblueSib $sendinblueSib,
        OrderInterface $order,
        CustomerAddress $customerAddress
    )
    {
        $this->sendinblueSib = $sendinblueSib;
        $this->order = $order;
        $this->customerAddress = $customerAddress;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $model = $this->sendinblueSib;
        $apiKeyV3 = $model->getDbData('api_key_v3');
        $trackStatus = $model->getDbData('ord_track_status');
        $dateValue = $model->getDbData('sendin_date_format');
        $orderStatus = $model->getDbData('api_sms_order_status');
        $senderOrder = $model->getDbData('sender_order');
        $senderOrderMessage = $model->getDbData('sender_order_message');
        $order = $observer->getEvent()->getData();
        $orderId = $order['order_ids'][0];
        /**
         * @FIXME user repository instead
         */
        $orderDatamodel = $this->order->load($orderId);
        $orderData = $orderDatamodel->getData();
        $email = $orderData['customer_email'];
        $NlStatus = $model->checkNlStatus($email);
        $orderID = $orderData['increment_id'];
        $orderPrice = $orderData['grand_total'];
        $dateAdded = $orderData['created_at'];
        $sibStatus = $model->syncSetting();
        if ($sibStatus == 1) {
            if (!empty($apiKeyV3)) {
                $mailin = $model->createObjSibClient();
            }

            $orderDate = date('Y-m-d', strtotime($dateAdded));

            if ($trackStatus == 1 && $NlStatus == 1 && !empty($apiKeyV3)) {
                $blacklistedValue = 0;
                $attrData = [];
                $attrData['ORDER_DATE'] = $orderDate;
                $attrData['ORDER_PRICE'] = $orderPrice;
                $attrData['ORDER_ID'] = $orderID;
                $dataSync = ["email" => $email,
                "attributes" => $attrData,
                "blacklisted" => false,
                "updateEnabled" => true
                ];
                $mailin->createUser($dataSync);
            }
            if ($orderStatus == 1 && !empty($senderOrder) && !empty($senderOrderMessage)) {
                $custId = $orderData['customer_id'];
                if (!empty($custId)) {
                    $customers = $model->_customers->load($custId);
                    $billingId =  $customers->getDefaultBilling();
                    $billingId = !empty($billingId) ? $billingId : $customers->getDefaultShipping();
                    /**
                     * @FIXME use repository instead
                     */
                    $address = $this->customerAddress->load($billingId);
                }

                $firstname = $address->getFirstname();
                $lastname = $address->getLastname();
                $telephone = !empty($address->getTelephone()) ? $address->getTelephone() : '';
                $countryId = !empty($address->getCountry()) ? $address->getCountry() : '';
                $smsVal = '';
                if (!empty($countryId) && !empty($telephone)) {
                    $countryCode = $model->getCountryCode($countryId);
                    if (!empty($countryCode)) {
                        $smsVal = $model->checkMobileNumber($telephone, $countryCode);
                    }
                }
                $firstName = str_replace('{first_name}', $firstname, $senderOrderMessage);
                $lastName = str_replace('{last_name}', $lastname."\r\n", $firstName);
                $procuctPrice = str_replace('{order_price}', $orderPrice, $lastName);
                $orderDate = str_replace('{order_date}', $orderDate."\r\n", $procuctPrice);
                $msgbody = str_replace('{order_reference}', $orderID, $orderDate);
                $smsData = [];

                if (!empty($smsVal)) {
                    $smsData['to'] = $smsVal;
                    $smsData['from'] = $senderOrder;
                    $smsData['text'] = $msgbody;
                    $smsData['type'] = 'transactional';
                    $model->sendSmsApi($smsData);
                }
            }
        }
    }
}
