<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Controller\Adminhtml\Rule;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Shipping rules grid page.
 */
class Index extends AbstractRule implements HttpGetActionInterface
{
    public function execute(): ResultInterface
    {
        /** @var \Magento\Backend\Model\View\Result\Page $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $result->setActiveMenu(self::ADMIN_RESOURCE);
        $result->getConfig()->getTitle()->prepend(__('Shipping Rules'));

        return $result;
    }
}
