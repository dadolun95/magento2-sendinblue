<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */
namespace Sendinblue\Sendinblue\Helper;

use Magento\Framework\App\Helper\Context;
use \Psr\Log\LoggerInterface;
use \Sendinblue\Sendinblue\Helper\ConfigHelper;

/**
 * Class ConfigHelper
 * @package Sendinblue\Sendinblue\Helper
 */
class DebugLogger extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var \Sendinblue\Sendinblue\Helper\ConfigHelper
     */
    protected $configHelper;
    /**
     * @var bool
     */
    protected $isDebug = false;

    /**
     * DebugLogger constructor.
     * @param Context $context
     * @param LoggerInterface $logger
     * @param \Sendinblue\Sendinblue\Helper\ConfigHelper $configHelper
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        ConfigHelper $configHelper
    )
    {
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->isDebug = $this->configHelper->getFlag('debug_enabled');
        parent::__construct($context);
    }

    /**
     * @param $message
     */
    public function log($message) {
        if ($this->isDebug) {
            $this->logger->info($message);
        }
    }
}
