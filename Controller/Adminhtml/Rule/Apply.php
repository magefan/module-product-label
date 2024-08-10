<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Controller\Adminhtml\Rule;

use Magento\Framework\Controller\ResultFactory;
use Magefan\ProductLabel\Model\Config\Source\ApplyByOptions;

/**
 * Class Apply
 */
class Apply extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magefan_ProductLabel::rule';

    /**
     * @var \Magefan\ProductLabel\Model\ProductLabelAction
     */
    protected $productLabelAction;

    /**
     * @var
     */
    protected $logger;

    /**
     * @var \Magefan\ProductLabel\Api\RuleCollectionInterfaceFactory
     */
    protected $ruleCollection;

    /**
     * @var \Magefan\ProductLabel\Model\Config
     */
    protected $config;

    /**
     * Apply constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magefan\ProductLabel\Model\ProductLabelAction $productLabelAction
     * @param \Magefan\ProductLabel\Api\RuleCollectionInterfaceFactory $ruleCollectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magefan\ProductLabel\Model\ProductLabelAction $productLabelAction,
        \Magefan\ProductLabel\Api\RuleCollectionInterfaceFactory $ruleCollectionFactory,
        \Magefan\ProductLabel\Model\Config $config
    ) {
        $this->config = $config;
        $this->ruleCollection = $ruleCollectionFactory;
        $this->productLabelAction = $productLabelAction;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->getUrl('*/*/index'));

        try {
            $countRules = $this->ruleCollection->create()->getSize();

            if (!$countRules) {
                $this->messageManager->addError(__('Cannot find any rule.'));
            }

            if ($this->config->isEnabled()) {
                $params = [];
                $params['rule_apply_type'] = ApplyByOptions::MANUALLY;
                
                if ($id = (int)$this->getRequest()->getParam('id')) {
                    $params['rule_id'] = $id;
                }

                $this->productLabelAction->execute($params);
                $this->messageManager->addSuccess(__('Rules has been applied.'));
            } else {
                $this->messageManager->addNotice(__('Please enable the extension to apply rules.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(__('Something went wrong. %1', $e->getMessage()));
        }

        return $resultRedirect;
    }
}
