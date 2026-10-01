<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Registry;
use Magento\Ui\Component\MassAction\Filter;
use Tonyapps\ShippingRules\Api\RuleRepositoryInterface;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule\CollectionFactory;
use Tonyapps\ShippingRules\Model\RuleFactory;

/**
 * Deletes the rules selected in the grid.
 */
class MassDelete extends AbstractRule implements HttpPostActionInterface
{
    public function __construct(
        Context $context,
        RuleRepositoryInterface $ruleRepository,
        RuleFactory $ruleFactory,
        Registry $coreRegistry,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory
    ) {
        parent::__construct($context, $ruleRepository, $ruleFactory, $coreRegistry);
    }

    public function execute(): Redirect
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deleted = 0;

        /** @var \Tonyapps\ShippingRules\Model\Rule $rule */
        foreach ($collection as $rule) {
            $this->ruleRepository->delete($rule);
            $deleted++;
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $deleted));

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/');
    }
}
