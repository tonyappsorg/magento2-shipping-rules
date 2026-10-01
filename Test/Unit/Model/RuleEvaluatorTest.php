<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Model;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Rule\Model\Condition\Combine;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule\Collection;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule\CollectionFactory;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\RuleEvaluator;

class RuleEvaluatorTest extends TestCase
{
    private const STORE_ID = 1;
    private const GROUP_ID = 2;
    private const TODAY = '2026-08-09';

    private CollectionFactory&MockObject $collectionFactory;
    private Collection&MockObject $collection;
    private Address&MockObject $address;
    private RateRequest $request;
    private RuleEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->collection = $this->createMock(Collection::class);
        $this->collection->method('addActiveFilter')->willReturnSelf();
        $this->collectionFactory->method('create')->willReturn($this->collection);

        $localeDate = $this->createMock(TimezoneInterface::class);
        $localeDate->method('date')->willReturn(new \DateTimeImmutable(self::TODAY));

        $this->address = $this->createMock(Address::class);

        $quote = $this->createMock(Quote::class);
        $quote->method('getStoreId')->willReturn(self::STORE_ID);
        $quote->method('getCustomerGroupId')->willReturn(self::GROUP_ID);

        $item = $this->createMock(AbstractItem::class);
        $item->method('getQuote')->willReturn($quote);
        $item->method('getAddress')->willReturn($this->address);

        $this->request = new RateRequest(['all_items' => [$item]]);

        $this->evaluator = new RuleEvaluator(
            $this->collectionFactory,
            $localeDate,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testReturnsEmptyWhenRequestHasNoItems(): void
    {
        $request = new RateRequest(['all_items' => []]);

        self::assertSame([], $this->evaluator->getMatchingRules($request));
    }

    public function testMatchesRuleWithinScopeAndValidConditions(): void
    {
        $rule = $this->rule([], [], null, null, true);
        $this->givenRules([$rule]);

        self::assertSame([$rule], $this->evaluator->getMatchingRules($this->request));
    }

    public function testExcludesRuleForOtherCustomerGroup(): void
    {
        $rule = $this->rule([], [99], null, null, true);
        $this->givenRules([$rule]);

        self::assertSame([], $this->evaluator->getMatchingRules($this->request));
    }

    public function testExcludesRuleForOtherStore(): void
    {
        $rule = $this->rule([99], [], null, null, true);
        $this->givenRules([$rule]);

        self::assertSame([], $this->evaluator->getMatchingRules($this->request));
    }

    public function testExcludesRuleNotYetActive(): void
    {
        $rule = $this->rule([], [], '2999-01-01', null, true);
        $this->givenRules([$rule]);

        self::assertSame([], $this->evaluator->getMatchingRules($this->request));
    }

    public function testExcludesRuleWithNonMatchingConditions(): void
    {
        $rule = $this->rule([], [], null, null, false);
        $this->givenRules([$rule]);

        self::assertSame([], $this->evaluator->getMatchingRules($this->request));
    }

    /**
     * @param array<int, Rule&MockObject> $rules
     */
    private function givenRules(array $rules): void
    {
        $this->collection->method('getIterator')->willReturn(new \ArrayIterator($rules));
    }

    /**
     * @param int[] $storeIds
     * @param int[] $groupIds
     * @return Rule&MockObject
     */
    private function rule(
        array $storeIds,
        array $groupIds,
        ?string $fromDate,
        ?string $toDate,
        bool $conditionsValid
    ): Rule {
        $conditions = $this->createMock(Combine::class);
        $conditions->method('validate')->with($this->address)->willReturn($conditionsValid);

        $rule = $this->createMock(Rule::class);
        $rule->method('getStoreIds')->willReturn($storeIds);
        $rule->method('getCustomerGroupIds')->willReturn($groupIds);
        $rule->method('getFromDate')->willReturn($fromDate);
        $rule->method('getToDate')->willReturn($toDate);
        $rule->method('getConditions')->willReturn($conditions);

        return $rule;
    }
}
