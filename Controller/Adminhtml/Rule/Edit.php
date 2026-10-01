<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Api\RuleRepositoryInterface;
use Tonyapps\ShippingRules\Model\RuleFactory;

/**
 * Renders the rule edit / new form.
 */
class Edit extends AbstractRule implements HttpGetActionInterface
{
    public function __construct(
        Context $context,
        RuleRepositoryInterface $ruleRepository,
        RuleFactory $ruleFactory,
        Registry $coreRegistry
    ) {
        parent::__construct($context, $ruleRepository, $ruleFactory, $coreRegistry);
    }

    public function execute(): ResultInterface
    {
        $ruleId = (int)$this->getRequest()->getParam('rule_id');

        if ($ruleId) {
            try {
                $rule = $this->ruleRepository->getById($ruleId);
            } catch (NoSuchEntityException) {
                $this->messageManager->addErrorMessage(__('This shipping rule no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        } else {
            $rule = $this->ruleFactory->create();
        }

        $this->coreRegistry->register(self::REGISTRY_KEY, $rule);

        /** @var \Magento\Backend\Model\View\Result\Page $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $result->setActiveMenu(self::ADMIN_RESOURCE);
        $result->getConfig()->getTitle()->prepend(
            $ruleId ? (string)$rule->getData(RuleInterface::NAME) : __('New Shipping Rule')
        );

        return $result;
    }
}
