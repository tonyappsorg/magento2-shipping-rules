<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Shared backend controller context for the rule controller tests.
 */
abstract class ControllerTestCase extends TestCase
{
    protected Http&MockObject $request;
    protected ManagerInterface&MockObject $messages;
    protected Redirect&MockObject $redirect;
    protected Context&MockObject $context;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->messages = $this->createMock(ManagerInterface::class);
        $this->redirect = $this->createMock(Redirect::class);
        $this->redirect->method('setPath')->willReturnSelf();
        $redirectFactory = $this->createMock(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($this->redirect);

        $this->context = $this->createMock(Context::class);
        $this->context->method('getRequest')->willReturn($this->request);
        $this->context->method('getMessageManager')->willReturn($this->messages);
        $this->context->method('getResultRedirectFactory')->willReturn($redirectFactory);
    }
}
