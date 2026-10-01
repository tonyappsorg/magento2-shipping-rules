<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Controller\Adminhtml\Rule;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * "Add New Rule" — forwards to the edit form.
 */
class NewAction extends AbstractRule implements HttpGetActionInterface
{
    public function execute(): ResultInterface
    {
        /** @var \Magento\Backend\Model\View\Result\Forward $forward */
        $forward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);

        return $forward->forward('edit');
    }
}
