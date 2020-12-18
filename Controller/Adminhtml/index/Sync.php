<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */

namespace Sendinblue\Sendinblue\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Sendinblue\Sendinblue\Model\SendinblueSib;

/**
 * Class Sync
 * @package Sendinblue\Sendinblue\Controller\Adminhtml\Index
 */
class Sync extends \Magento\Backend\App\Action
{

    /**
     * @var SendinblueSib
     */
    protected $sendinblueSib;

    /**
     * Sync constructor.
     * @param Action\Context $context
     * @param SendinblueSib $sendinblueSib
     */
    public function __construct(
        Action\Context $context,
        SendinblueSib $sendinblueSib
    )
    {
        $this->sendinblueSib = $sendinblueSib;
        parent::__construct($context);
    }

    /**
     * Post user question
     * @return void
     * @throws \Exception
     */
    public function execute()
    {
        $this->syncNewsletterData();
    }

    /**
     * Determine if authorized to perform group actions.
     *
     * @return bool
     */
    public function _isAllowed()
    {
        return true;
    }

    /**
     * Get users status and update it
     */
    public function syncNewsletterData()
    {
        $model = $this->sendinblueSib;
        $apiKey = $model->getDbData('api_key');
        $connection = $model->createDbConnection();
        $tblNewsletter = $model->tbWithPrefix('newsletter_subscriber');
        if (!empty($apiKey)) {
            $mailin = $model->createObjMailin($apiKey);
            $sibData = [];
            $listVal = $model->getDbData('selected_list_data');
            $sibData['listids'] = str_replace(',', '|', $listVal);
            $blockUsersLists = $mailin->getListUsersBlacklistStatus($sibData);
            $blockUsers = $blockUsersLists['data'];
            foreach ($blockUsers as $newsletterData) {
                if (!empty($newsletterData)) {
                    foreach ($newsletterData as $nlData) {
                        $status = $model->checkNlStatus($nlData['email']);
                        if (!empty($status)) {
                            $nlStatus = ($nlData['blacklisted'] == 1) ? 3 : 1;
                            $email = $nlData['email'];
                            $sql = $connection->query('Update ' . $tblNewsletter . ' Set subscriber_status = '.$nlStatus.' WHERE subscriber_email ='."'$email'");
                        }
                    }
                }
            }
        }
        $this->messageManager->addSuccess(__('The CRON has been well executed.'));
        $this->_redirect('sendinblue/sib/index');
        return true;
    }
}
