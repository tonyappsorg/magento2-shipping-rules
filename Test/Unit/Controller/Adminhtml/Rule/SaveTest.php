<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Controller\Adminhtml\Rule;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
use Tonyapps\ShippingRules\Api\RuleRepositoryInterface;
use Tonyapps\ShippingRules\Controller\Adminhtml\Rule\Save;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\RuleFactory;

class SaveTest extends ControllerTestCase
{
    public function testMalformedDateGoesBackToTheFormWithAnError(): void
    {
        $post = ['name' => 'Embargo', 'from_date' => 'not a date'];
        $this->request->method('getPostValue')->willReturn($post);

        $rule = $this->createMock(Rule::class);
        $rule->method('loadPost')->willThrowException(new \Exception('Failed to parse time string'));
        $ruleFactory = $this->createMock(RuleFactory::class);
        $ruleFactory->method('create')->willReturn($rule);
        $repository = $this->createMock(RuleRepositoryInterface::class);
        $repository->expects(self::never())->method('save');
        $persistor = $this->createMock(DataPersistorInterface::class);
        $persistor->expects(self::once())->method('set')->with(Save::DATA_PERSISTOR_KEY, $post);

        $this->messages->expects(self::once())->method('addErrorMessage')->with('Failed to parse time string');
        $this->redirect->expects(self::once())->method('setPath')->with('*/*/new')->willReturnSelf();

        $controller = new Save($this->context, $repository, $ruleFactory, $this->createMock(Registry::class), $persistor);

        self::assertSame($this->redirect, $controller->execute());
    }
}
