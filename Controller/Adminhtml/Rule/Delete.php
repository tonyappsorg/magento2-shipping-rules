<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Controller\Adminhtml\Rule;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Deletes a single shipping rule. POST only: the grid action and the edit-page button both post, and a delete
 * shouldn't happen from a followed link.
 */
class Delete extends AbstractRule implements HttpPostActionInterface
{
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $ruleId = (int)$this->getRequest()->getParam('rule_id');

        if (!$ruleId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a shipping rule to delete.'));

            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->ruleRepository->deleteById($ruleId);
            $this->messageManager->addSuccessMessage(__('The shipping rule has been deleted.'));

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());

            return $resultRedirect->setPath('*/*/edit', ['rule_id' => $ruleId]);
        }
    }
}
