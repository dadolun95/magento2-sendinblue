<?php
/**
 * @category    Magento 2
 * @package     Sendinblue_Sendinblue
 * URL:  https:www.sendinblue.com
 */

namespace Sendinblue\Sendinblue\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKeyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Sendinblue\Sendinblue\Model\SendinblueSib;

/**
 * Class SendinblueBlock
 * @package Sendinblue\Sendinblue\Block\Adminhtml
 */
class SendinblueBlock extends Template
{
    /**
     * @var FormKeyFactory
     */
    protected $formKeyFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var SendinblueSib
     */
    protected $sendinblueSib;

    /**
     * SendinblueBlock constructor.
     * @param Context $context
     * @param FormKeyFactory $formKeyFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param SendinblueSib $sendinblueSib
     * @param array $data
     */
    public function __construct(
        Context $context,
        FormKeyFactory $formKeyFactory,
        ScopeConfigInterface $scopeConfig,
        SendinblueSib $sendinblueSib,
        array $data = []
    ) {
        $this->formKeyFactory = $formKeyFactory;
        $this->scopeConfig = $scopeConfig;
        $this->sendinblueSib = $sendinblueSib;
        parent::__construct($context, $data);
    }

    /**
     * @return SendinblueSib
     */
    public function getSibModel() {
        return $this->sendinblueSib;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFormValue()
    {
        /**
         * @var \Magento\Framework\Data\Form\FormKey $formKey
         */
        $formKey = $this->formKeyFactory->create();
        return $formKey->getFormKey();
    }
}
