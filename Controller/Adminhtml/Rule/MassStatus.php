<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Registry;
use Magento\Ui\Component\MassAction\Filter;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Api\RuleRepositoryInterface;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule\CollectionFactory;
use Tonyapps\ShippingRules\Model\RuleFactory;

/**
 * Enables / disables the rules selected in the grid.
 */
class MassStatus extends AbstractRule implements HttpPostActionInterface
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
        $isActive = (int)$this->getRequest()->getParam('status') === 1;
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $updated = 0;

        /** @var \Tonyapps\ShippingRules\Model\Rule $rule */
        foreach ($collection as $rule) {
            // One bad row shouldn't abort the rest of the batch with an error page.
            try {
                $rule->setData(RuleInterface::IS_ACTIVE, $isActive ? 1 : 0);
                $this->ruleRepository->save($rule);
                $updated++;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Rule "%1" could not be updated: %2', $rule->getName(), $e->getMessage())
                );
            }
        }

        if ($updated) {
            $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been updated.', $updated));
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/');
    }
}
