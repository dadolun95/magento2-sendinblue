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
 * Class Post
 * @package Sendinblue\Sendinblue\Controller\Adminhtml\Index
 */
class Post extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Sendinblue_Sendinblue::sendinblue';

    /**
     * @var SendinblueSib
     */
    protected $sendinblueSib;

    /**
     * Post constructor.
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
     *
     * @return void
     * @throws \Exception
     */

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        if (!$post) {
            $this->_redirect('*/*/');
            return;
        }
        try {
            $model = $this->sibObject();

            if (isset($post['submitUpdate']) && !empty($post['submitUpdate'])) {
                $this->apiKeyPostProcessConfiguration();
            }

            if (isset($post['submitForm2']) && $post['update_tempvalue'] == 'update_tempvalue') {
                $this->saveTemplateValue();
            }

            if (isset($post['submitUpdateImport']) && $post['import_function'] == 'import_function') {
                $listId = $model->getDbData('selected_list_data');
                $resp = $model->sendAllMailIDToSendin($listId);
                if ($resp == 0) {
                    $this->messageManager->addSuccess(__('Old subscribers imported successfully'));
                    $this->_redirect('sendinblue/sib/index');
                    return;
                } else {
                    $this->messageManager->addError(__('Old subscribers not imported successfully, please click on Import Old Subscribers button to import them again'));
                    $this->_redirect('sendinblue/sib/index');
                    return;
                }
            }
            //save value for notify email
            if (isset($post['notify_sms_mail']) && !empty($post['notify_sms_mail'])) {
                $this->saveNotifyValue();
            }

            //save order sms send and body details
            if (isset($post['sender_order_save']) && !empty($post['sender_order_save'])) {
                $this->saveOrderSms();
            }

            //save shipped sms send and body details
            if (isset($post['sender_shipment_save']) && !empty($post['sender_shipment_save'])) {
                $this->saveShippedSms();
            }

            /**
             * Description: send single and multi user campaign for subcribe and All Users.
             *
             */
            if (isset($post['sender_campaign_save']) && ($post['campaign_save_function'] == 'campaign_save_function')) {
                $returnResp = $this->sendSmsCampaign();
                if ($returnResp == 'success') {
                    $this->messageManager->addSuccess(__('Campaign has been scheduled successfully'));
                    $this->_redirect('sendinblue/sib/index');
                    return;
                } else {
                    $this->messageManager->addError(__('Campaign failed'));
                    $this->_redirect('sendinblue/sib/index');
                    return;
                }
            }
            /**
             * Description: send test email if smtp setup well.
             *
             */
            if (isset($post['sendTestMail']) && !empty($post['sendTestMail'])) {
                $post = $this->getRequest()->getPostValue();
                $userEmail = !empty($post['testEmail']) ? $post['testEmail'] : '';
                $relayData = $model->getDbData('relay_data_status');
                if (!empty($userEmail) && $post['smtpservices'] == 1) {
                    if ($relayData == 'enabled') {
                        $title = __('[Sendinblue SMTP] test email');
                        $tempName = 'sendinsmtp_conf';
                        $respMail = $model->smtpSendMail($userEmail, $title, $tempName, $paramVal = '');
                        if ($respMail['status'] == 1) {
                            $this->messageManager->addSuccess(__('Mail sent'));
                            $this->_redirect('sendinblue/sib/index');
                            return;
                        } else {
                            $this->messageManager->addError(__('Mail not sent'));
                            $this->_redirect('sendinblue/sib/index');
                            return;
                        }
                    } else {
                        $this->messageManager->addError(__('Your SMTP account is not activated and therefore you can\'t use Sendinblue SMTP. For more informations, Please contact our support to: contact@sendinblue.com'));
                        $this->_redirect('sendinblue/sib/index');
                        return;
                    }
                } else {
                    $this->messageManager->addError(__('Put valid email'));
                    $this->_redirect('sendinblue/sib/index');
                    return;
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(
                $e->getMessage()
            );
            $this->_redirect('sendinblue/sib/index');
            return;
        }
    }

    public function apiKeyPostProcessConfiguration()
    {
        $post = $this->getRequest()->getPostValue();

        if (!$post) {
            $this->_redirect('sendinblue/sib/index');
            return;
        }
        try {

            $model = $this->sibObject();

            $error = false;
            if ($post['apikey'] === null || $post['apikey'] === '') {
                $error = true;
            }
            if ($post['status'] === null || $post['status'] === '') {
                $error = true;
            }
            if ($post['submitUpdate'] === null || $post['submitUpdate'] === '') {
                $error = true;
            }
            if ($error) {
                throw new \Exception(__('API key is invalid.'));
            }
            $apikey = trim($post['apikey']);
            $status = trim($post['status']);
            if ($status == 1) {
                $oldApiKey = trim($model->getDbData('api_key_v3'));
                if ($apikey != $oldApiKey) {
                    $model->resetDataBaseValue();
                    $model->resetSmtpDetail();
                }
                $model->updateDbData('api_key_v3', $apikey);
                $model->updateDbData('api_key_status', $status);
                $sendinListdata = $model->getDbData('selected_list_data');
                $sendinFirstrequest = $model->getDbData('first_request');

                if (empty($sendinListdata) && empty($sendinFirstrequest)) {
                    $model->updateDbData('first_request', 1);
                    $model->updateDbData('subscribe_setting', 1);
                    $model->updateDbData('notify_cron_executed', 0);
                    $model->updateDbData('syncronize', 1);
                }

                $response = $model->checkApikey($apikey);

                if ($response) {
                    $model->createAttributesName($apikey, $response);
                    $model->createFolderName($apikey);
                    $this->messageManager->addSuccess(
                        __('Sendiblue configuration setting Successfully updated')
                    );
                    $this->_redirect('sendinblue/sib/index');
                    return;
                } else {
                    //We reset all settings  in case the API key is invalid.
                    $model->updateDbData('api_key_status', 0);
                    $model->resetDataBaseValue();
                    $this->messageManager->addError(
                        __('API key is invalid.')
                    );
                    $this->_redirect('sendinblue/sib/index');
                    return;
                }
            }
        } catch (\Exception $e) {
            $model->updateDbData('api_key_status', 0);
            $model->resetDataBaseValue();
            $this->messageManager->addError(
                __($e->getMessage())
            );
            $this->_redirect('sendinblue/sib/index');
            return;
        }
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

    /**
     * @return SendinblueSib
     */
    public function sibObject()
    {
        return $this->sendinblueSib;
    }

    /**
     * @return bool
     * @throws \SendinBlue\Client\ApiException
     */
    public function saveTemplateValue()
    {
        $model = $this->sibObject();
        $post = $this->getRequest()->getPostValue();
        $valueTemplateId = !empty($post['template']) ? $post['template'] : '';
        $doubleOptinTempId = !empty($post['doubleoptin_template_id']) ? $post['doubleoptin_template_id'] : '';
        $subscribeConfirmType = !empty($post['subscribe_confirm_type']) ? $post['subscribe_confirm_type'] : '';
        $optinRedirectUrlCheck = !empty($post['optin_redirect_url_check']) ? $post['optin_redirect_url_check'] : '';
        $doubleoptinRedirectUrl = !empty($post['doubleoptin_redirect_url']) ? $post['doubleoptin_redirect_url'] : '';
        $finalConfirmEmail = !empty($post['final_confirm_email']) ? $post['final_confirm_email'] : '';
        $finalTempId = !empty($post['template_final']) ? $post['template_final'] : '';
        $manageSubscribe = !empty($post['managesubscribe']) ? $post['managesubscribe'] : 0;
        $shopApiKeyStatus = $model->getDbData('api_key_status');

        $model->updateDbData('doubleoptin_template_id', $doubleOptinTempId);
        $model->updateDbData('template_id', $valueTemplateId);
        $model->updateDbData('optin_url_check', $optinRedirectUrlCheck);
        $model->updateDbData('doubleoptin_redirect', $doubleoptinRedirectUrl);
        $model->updateDbData('final_confirm_email', $finalConfirmEmail);
        $model->updateDbData('subscribe_setting', $manageSubscribe);
        if (!empty($finalTempId)) {
            $model->updateDbData('final_template_id', $finalTempId);
        }
        $model->updateSender();
        if (!empty($subscribeConfirmType)) {
            $model->updateDbData('confirm_type', $subscribeConfirmType);
            if ($subscribeConfirmType == 'doubleoptin') {
                $resOptin = $model->checkFolderListDoubleoptin();
                if (!empty($resOptin['optin_id'])) {
                    $model->updateDbData('optin_list_id', $resOptin['optin_id']);
                }

                if ( $resOptin === false && !empty($shopApiKeyStatus) ) {
                    /**
                     * @var \Sendinblue\Sendinblue\Model\SibClient $sibClient
                     */
                    $sibClient = $model->createSibClient();
                    $data = ["name"=> "FORM"];
                    $folderRes = $sibClient->createFolder($data);
                    if (201 === $sibClient->getLastResponseCode()) {
                        $data = [
                          "name" => 'Temp - DOUBLE OPTIN',
                          "folderId" => $folderRes["id"]
                        ];
                        $listResp = $sibClient->createList($data);
                        if (201 === $sibClient->getLastResponseCode()) {
                            $listId = $listResp['id'];
                            $model->updateDbData('optin_list_id', $listId);
                        }
                    }
                }
            }
        }
        $displayList = $post['display_list'];
        if (!empty($displayList)) {
                $listValue = implode('|', $displayList);
                $model->updateDbData('selected_list_data', $listValue);
        }

        $this->messageManager->addSuccess(__('Sendiblue configuration setting Successfully updated'));
        $this->_redirect('sendinblue/sib/index');
        return true;
    }

    /**
     * Description: Save sms limit warning details in DB
     *
     */
    public function saveNotifyValue()
    {
        $post = $this->getRequest()->getPostValue();
        $model = $this->sibObject();
        if (!empty($post['value_notify_email']) && !empty($post['notify_value'])) {
            $model->updateDbData('notify_email', $post['value_notify_email']);
            $model->updateDbData('notify_value', $post['notify_value']);
            $model->updateDbData('notify_email_send', 0);
        }
        $this->messageManager->addSuccess(__('Sendiblue configuration setting Successfully updated'));
        $this->_redirect('sendinblue/sib/index');
        return true;
    }

    /**
     * Description: Save sms Order confirmation sender and body.
     *
     */
    public function saveOrderSms()
    {
        $post = $this->getRequest()->getPostValue();
        $model = $this->sibObject();
        if (!empty($post['sender_order']) && !empty($post['sender_order_message'])) {
            $model->updateDbData('sender_order', $post['sender_order']);
            $model->updateDbData('sender_order_message', $post['sender_order_message']);
        }
        $this->messageManager->addSuccess(__('Sendiblue configuration setting Successfully updated'));
        $this->_redirect('sendinblue/sib/index');
        return true;
    }

    /**
     * Description: Save sms Order shipped sender and body.
     *
     */
    public function saveShippedSms()
    {
        $post = $this->getRequest()->getPostValue();
        $model = $this->sibObject();
        if (!empty($post['sender_shipment']) && !empty($post['sender_shipment_message'])) {
            $model->updateDbData('sender_shipment', $post['sender_shipment']);
            $model->updateDbData('sender_shipment_message', $post['sender_shipment_message']);
        }
        $this->messageManager->addSuccess(__('Sendiblue configuration setting Successfully updated'));
        $this->_redirect('sendinblue/sib/index');
        return true;
    }

    /**
     * Description: This method is called when the user sets the Campaign Sms and hits the submit button.
     */
    public function sendSmsCampaign()
    {
        $post = $this->getRequest()->getPostValue();
        $sendinSmsChoice = $post['Sms_Choice'];
        $model = $this->sibObject();
        if (!empty($post)) {
            if ($sendinSmsChoice == 1) {
               return $result = $model->singleChoiceCampaign($post);
            } elseif ($sendinSmsChoice == 0) {
                return $result = $model->multipleChoiceCampaign($post);
            } elseif ($sendinSmsChoice == 2) {
               return $result = $model->multipleChoiceSubCampaign($post);
            }
        }
    }
}

