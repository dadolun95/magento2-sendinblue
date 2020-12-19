<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */
namespace Sendinblue\Sendinblue\ViewModel;

use Sendinblue\Sendinblue\Helper\ConfigHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Cookie\Helper\Cookie as CookieHelper;

/**
 * Class AutomationTrackingManager
 * @package Sendinblue\Sendinblue\ViewModel
 */
class AutomationTrackingManager implements \Magento\Framework\View\Element\Block\ArgumentInterface
{

    /**
     * @var ConfigHelper
     */
    protected $configHelper;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CookieHelper
     */
    protected $cookieHelper;

    /**
     * AutomationTrackingManager constructor.
     * @param ConfigHelper $configHelper
     * @param StoreManagerInterface $storeManager
     * @param CookieHelper $cookieHelper
     */
    public function __construct(
        ConfigHelper $configHelper,
        StoreManagerInterface $storeManager,
        CookieHelper $cookieHelper
    ) {
        $this->configHelper = $configHelper;
        $this->storeManager = $storeManager;
        $this->cookieHelper = $cookieHelper;
    }

    /**
     * Return cookie restriction mode value
     *
     * @return bool
     */
    public function isCookieRestrictionModeEnabled()
    {
        return $this->cookieHelper->isCookieRestrictionModeEnabled();
    }

    /**
     * Return current website id
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCurrentWebsiteId()
    {
        return $this->storeManager->getWebsite()->getId();
    }

    /**
     * @return mixed
     */
    public function getApiKey()
    {
        $apiKey = '';
        $sibStatus = $this->configHelper->syncSetting();
        $maKey = $this->configHelper->getData('sib_automation_key');
        $trackingStatus = $this->configHelper->getData('sib_track_status');
        if ($sibStatus == 1 && $trackingStatus == 1 && !empty($maKey)) {
            $apiKey = $maKey;
        }
        return $apiKey;
    }
}
