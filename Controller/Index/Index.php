<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */
namespace Sendinblue\Sendinblue\Controller\Index;

use Magento\Framework\App\Action\Context;
use Sendinblue\Sendinblue\Model\SendinblueSib;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Index
 * @package Sendinblue\Sendinblue\Controller\Index
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;
    /**
     * @var SendinblueSib
     */
    protected $_model;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Index constructor.
     * @param Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param SendinblueSib $sendinblueSib
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        SendinblueSib $sendinblueSib,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->_resultPageFactory = $resultPageFactory;
        $this->_model = $sendinblueSib;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \SendinBlue\Client\ApiException
     */
    public function execute()
    {
        $getValue = $this->getRequest()->getParam('value');
        $userEmail = base64_decode($getValue);
        $this->dubleoptinProcess($userEmail);
    }

    /**
     * Get response, send confirm subscription mail and redirect in given url
     *
     * @param $userEmail
     * @throws \SendinBlue\Client\ApiException
     */
    public function dubleoptinProcess($userEmail)
    {
        $nlStatus = $this->_model->checkNlStatus($userEmail);
        if (!empty($userEmail) && $nlStatus == 1) {
            $optinListId = $this->_model->getDbData('optin_list_id');
            $listId = $this->_model->getDbData('selected_list_data');

            /**
             * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
             */
            $sibClient = $this->_model->createSibClient();

            $data = array(
                    "attributes" => array("DOUBLE_OPT-IN"=>'1'),
                    "emailBlacklisted" => false,
                    "listIds" => array_map('intval', explode('|', $listId)),
                    "unlinkListIds" => array_map('intval', explode('|', $optinListId)),
                    "smsBlacklisted" => false
                );

            $sibClient->updateUser($userEmail, $data);

            $confirmEmail = $this->_model->getDbData('final_confirm_email');
            if ($confirmEmail === 'yes') {
                $finalId = $this->_model->getDbData('final_template_id');
                $this->_model->sendOptinConfirmMailResponce($userEmail, $finalId);
            }
        }
        $doubleoptinRedirect = $this->_model->getDbData('doubleoptin_redirect');
        $optinUrlCheck = $this->_model->getDbData('optin_url_check');
        if ($optinUrlCheck === 'yes' && !empty($doubleoptinRedirect)) {
            header("Location: ".$doubleoptinRedirect);
            ob_flush_end();
        } else {
            $shopName = $this->scopeConfig->getValue('web/unsecure/base_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            header("Location: ".$shopName);
            ob_flush_end();
        }
    }

}
