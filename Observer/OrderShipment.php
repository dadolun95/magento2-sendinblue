<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */
namespace Sendinblue\Sendinblue\Observer;

use Magento\Customer\Api\AddressRepositoryInterface as CustomerAddressRepository;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use \Sendinblue\Sendinblue\Model\SendinblueSib;
use \Sendinblue\Sendinblue\Helper\DebugLogger;

/**
 * Class OrderShipment
 * @package Sendinblue\Sendinblue\Observer
 */
class OrderShipment implements ObserverInterface
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
     * OrderShipment constructor.
     * @param SendinblueSib $sendinblueSib
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerAddressRepository $customerAddressRepository
     * @param DebugLogger $debugLogger
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
        $this->debugLogger->log(__('OrderShipment observer START'));
        $model = $this->sendinblueSib;
        /**
         * @var \Magento\Sales\Api\Data\ShipmentInterface $shipment
         */
        $shipment = $observer->getEvent()->getShipment();
        /**
         * @var \Magento\Sales\Api\Data\OrderInterface $order
         */
        $order = $shipment->getOrder();
        $dateValue = $model->getDbData('sendin_date_format');
        $orderStatus = $model->getDbData('api_sms_shipment_status');
        $senderOrder = $model->getDbData('sender_shipment');
        $senderOrderMessage = $model->getDbData('sender_shipment_message');
        $orderId = $order->getEntityId();
        $orderDatamodel = $this->orderRepository->get($orderId);
        $orderData = $orderDatamodel->getData();
        $email = $orderData['customer_email'];
        $orderID = $orderData['increment_id'];
        $orderPrice = $orderData['grand_total'];
        $dateAdded = $orderData['created_at'];
        $sibStatus = $model->syncSetting();
        $this->debugLogger->log(__('Try update order %1 on shipment', $orderID));
        if ($sibStatus == 1) {
            if (!empty($dateValue) && $dateValue == 'dd-mm-yyyy') {
                $orderDate = date('d-m-Y', strtotime($dateAdded));
            } else {
                $orderDate = date('m-d-Y', strtotime($dateAdded));
            }

            if ($orderStatus == 1 && !empty($senderOrder) && !empty($senderOrderMessage)) {
                $custId = $orderData['customer_id'];
                if (!empty($custId)) {
                    $customer = $model->getCustomer($custId);
                    $shoppingId =  $customer->getDefaultShipping();
                    $address = $this->customerAddressRepository->getById($shoppingId);
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
        $this->debugLogger->log(__('OrderShipment observer END'));
    }
}
