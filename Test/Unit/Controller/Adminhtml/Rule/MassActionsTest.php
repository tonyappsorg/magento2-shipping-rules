<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Controller\Adminhtml\Rule;

use Magento\Framework\Registry;
use Magento\Ui\Component\MassAction\Filter;
use Tonyapps\ShippingRules\Api\RuleRepositoryInterface;
use Tonyapps\ShippingRules\Controller\Adminhtml\Rule\MassDelete;
use Tonyapps\ShippingRules\Controller\Adminhtml\Rule\MassStatus;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule\Collection;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule\CollectionFactory;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\RuleFactory;

/**
 * One failing row must not abort the batch: the rest are processed and the failure is reported.
 */
class MassActionsTest extends ControllerTestCase
{
    public function testMassDeleteCarriesOnPastAFailingRow(): void
    {
        [$bad, $good] = [$this->rule('Bad'), $this->rule('Good')];
        $repository = $this->createMock(RuleRepositoryInterface::class);
        $repository->expects(self::exactly(2))->method('delete')->willReturnCallback(
            fn(Rule $rule) => $rule === $bad ? throw new \Exception('locked') : true
        );
        $this->expectMessages('Rule "Bad" could not be deleted: locked', 'A total of 1 record(s) have been deleted.');

        (new MassDelete(
            $this->context,
            $repository,
            $this->createMock(RuleFactory::class),
            $this->createMock(Registry::class),
            ...$this->selection([$bad, $good])
        ))->execute();
    }

    public function testMassStatusCarriesOnPastAFailingRow(): void
    {
        [$bad, $good] = [$this->rule('Bad'), $this->rule('Good')];
        $this->request->method('getParam')->with('status')->willReturn('1');
        $repository = $this->createMock(RuleRepositoryInterface::class);
        $repository->expects(self::exactly(2))->method('save')->willReturnCallback(
            fn(Rule $rule) => $rule === $bad ? throw new \Exception('locked') : $rule
        );
        $this->expectMessages('Rule "Bad" could not be updated: locked', 'A total of 1 record(s) have been updated.');

        (new MassStatus(
            $this->context,
            $repository,
            $this->createMock(RuleFactory::class),
            $this->createMock(Registry::class),
            ...$this->selection([$bad, $good])
        ))->execute();
    }

    private function expectMessages(string $error, string $success): void
    {
        $this->messages->expects(self::once())->method('addErrorMessage')
            ->with(self::callback(fn($message) => (string)$message === $error));
        $this->messages->expects(self::once())->method('addSuccessMessage')
            ->with(self::callback(fn($message) => (string)$message === $success));
    }

    /**
     * @param Rule[] $rules
     * @return array{0: Filter, 1: CollectionFactory}
     */
    private function selection(array $rules): array
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($rules));
        $filter = $this->createMock(Filter::class);
        $filter->method('getCollection')->willReturn($collection);
        $factory = $this->createMock(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        return [$filter, $factory];
    }

    private function rule(string $name): Rule
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getName')->willReturn($name);

        return $rule;
    }
}
