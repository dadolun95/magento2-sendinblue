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
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Class SibAutomationObserver
 * @package Sendinblue\Sendinblue\Observer
 */
class SibAutomationObserver implements ObserverInterface
{

    /**
     * @var SendinblueSib
     */
    protected $sendinblueSib;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * SibAutomationObserver constructor.
     * @param SendinblueSib $sendinblueSib
     * @param CustomerSession $customerSession
     */
    public function __construct(
        SendinblueSib $sendinblueSib,
        CustomerSession $customerSession
    )
    {
        $this->sendinblueSib = $sendinblueSib;
        $this->customerSession = $customerSession;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        ob_start();
        $userEmail = '';
        $model = $this->sendinblueSib;
        $customerSession = $this->customerSession;
        if ($customerSession->isLoggedIn()) {
            $userEmail = $customerSession->getCustomer()->getEmail();
        }

        $sibStatus = $model->syncSetting();
        $maKey = $model->getDbData('sib_automation_key');
        $trackingStatus = $model->getDbData('sib_track_status');
        if ($sibStatus == 1 && $trackingStatus == 1 && !empty($maKey)) {
            $scriptVal = <<<EOT
                    <script type="text/javascript">
                        (function() {
                            window.sib = { equeue: [], client_key: "$maKey" };
                            /* OPTIONAL: email for identify request*/
                            window.sib.email_id = "$userEmail";
                            window.sendinblue = {}; for (var j = ['track', 'identify', 'trackLink', 'page'], i = 0; i < j.length; i++) { (function(k) { window.sendinblue[k] = function() { var arg = Array.prototype.slice.call(arguments); (window.sib[k] || function() { var t = {}; t[k] = arg; window.sib.equeue.push(t);})(arg[0], arg[1], arg[2]);};})(j[i]);}var n = document.createElement("script"),i = document.getElementsByTagName("script")[0]; n.type = "text/javascript", n.id = "sendinblue-js", n.async = !0, n.src = "https://sibautomation.com/sa.js?key=" + window.sib.client_key, i.parentNode.insertBefore(n, i), window.sendinblue.page();
                        })();
                    </script>
EOT;

            $controller = $observer->getControllerAction();
            $controller->getResponse()->setBody($scriptVal);
            return $this;
        }
    }
}
