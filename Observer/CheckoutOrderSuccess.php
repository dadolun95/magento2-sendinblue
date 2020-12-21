<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */
namespace Sendinblue\Sendinblue\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use \Sendinblue\Sendinblue\Model\SendinblueSib;
use Magento\Customer\Api\AddressRepositoryInterface as CustomerAddressRepository;
use \Sendinblue\Sendinblue\Helper\DebugLogger;

/**
 * Class CheckoutOrderSuccess
 * @package Sendinblue\Sendinblue\Observer
 */
class CheckoutOrderSuccess implements ObserverInterface
{

    /**
     * @var SendinblueSib
     */
    protected $sendinblueSib;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var CustomerAddressRepository
     */
    protected $customerAddressRepository;
    /**
     * @var DebugLogger
     */
    protected $debugLogger;

    /**
     * CheckoutOrderSuccess constructor.
     * @param SendinblueSib $sendinblueSib
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerAddressRepository $customerAddressRepository
     * @param DebugLogger $logger
     */
    public function __construct(
        SendinblueSib $sendinblueSib,
        OrderRepositoryInterface $orderRepository,
        CustomerAddressRepository $customerAddressRepository,
        DebugLogger $debugLogger
    )
    {
        $this->sendinblueSib = $sendinblueSib;
        $this->orderRepository = $orderRepository;
        $this->customerAddressRepository = $customerAddressRepository;
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
        $this->debugLogger->log(__('CheckoutOrderSuccess observer START'));
        $model = $this->sendinblueSib;
        $sibClient = null;
        $apiKeyV3 = $model->getDbData('api_key_v3');
        $trackStatus = $model->getDbData('ord_track_status');
        $orderStatus = $model->getDbData('api_sms_order_status');
        $senderOrder = $model->getDbData('sender_order');
        $senderOrderMessage = $model->getDbData('sender_order_message');
        $order = $observer->getEvent()->getData();
        $orderId = $order['order_ids'][0];
        $orderDatamodel = $this->orderRepository->get($orderId);
        $orderData = $orderDatamodel->getData();
        $email = $orderData['customer_email'];
        $NlStatus = $model->checkNlStatus($email);
        $orderID = $orderData['increment_id'];
        $orderPrice = $orderData['grand_total'];
        $dateAdded = $orderData['created_at'];
        $sibStatus = $model->syncSetting();
        $this->debugLogger->log(__('Try update order %1 for user with email: %2', $orderId, $email));
        if ($sibStatus == 1) {
            if (!empty($apiKeyV3)) {
                /**
                 * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
                 */
                $sibClient = $model->createSibClient();
            }

            $orderDate = date('Y-m-d', strtotime($dateAdded));

            if ($trackStatus == 1 && $NlStatus == 1 && $sibClient !== null) {
                $attrData = [];
                $attrData['ORDER_DATE'] = $orderDate;
                $attrData['ORDER_PRICE'] = $orderPrice;
                $attrData['ORDER_ID'] = $orderID;
                $dataSync = ["email" => $email,
                "attributes" => $attrData,
                "blacklisted" => false,
                "updateEnabled" => true
                ];
                $sibClient->createUser($dataSync);
            }
            if ($orderStatus == 1 && !empty($senderOrder) && !empty($senderOrderMessage)) {
                $custId = $orderData['customer_id'];
                if (!empty($custId)) {
                    $customers = $model->getCustomer($custId);
                    $billingId =  $customers->getDefaultBilling();
                    $billingId = !empty($billingId) ? $billingId : $customers->getDefaultShipping();
                    $address = $this->customerAddressRepository->getById($billingId);
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
        } else {
            $this->debugLogger->log(__('Contact Sync is not enabled'));
        }
        $this->debugLogger->log(__('CheckoutOrderSuccess observer END'));
    }
}
