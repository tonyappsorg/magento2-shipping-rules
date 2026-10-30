<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Model\Rule\Io;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\Rule\Io\RuleArrayConverter;
use Tonyapps\ShippingRules\Model\RuleFactory;

class RuleArrayConverterTest extends TestCase
{
    private RuleFactory&MockObject $ruleFactory;
    private RuleArrayConverter $converter;

    protected function setUp(): void
    {
        $this->ruleFactory = $this->createMock(RuleFactory::class);
        $this->converter = new RuleArrayConverter($this->ruleFactory);
    }

    public function testToArrayExposesEveryFieldAndTheConditionTree(): void
    {
        $conditions = $this->createMock(\Magento\Rule\Model\Condition\Combine::class);
        $conditions->method('asArray')->willReturn(['type' => 'combine']);

        $rule = $this->createMock(Rule::class);
        $rule->method('getName')->willReturn('Embargo');
        $rule->method('getDescription')->willReturn('Blocks embargoed destinations');
        $rule->method('isActive')->willReturn(true);
        $rule->method('getSortOrder')->willReturn(10);
        $rule->method('getAction')->willReturn(RuleInterface::ACTION_HIDE);
        $rule->method('getAppliesToMethods')->willReturn(['flatrate_flatrate']);
        $rule->method('getHideOthers')->willReturn(false);
        $rule->method('getCustomerGroupIds')->willReturn([1]);
        $rule->method('getStoreIds')->willReturn([]);
        $rule->method('getFromDate')->willReturn('2026-01-01');
        $rule->method('getToDate')->willReturn(null);
        $rule->method('getConditions')->willReturn($conditions);

        $result = $this->converter->toArray($rule);

        self::assertSame(
            [
                RuleInterface::NAME => 'Embargo',
                RuleInterface::DESCRIPTION => 'Blocks embargoed destinations',
                RuleInterface::IS_ACTIVE => 1,
                RuleInterface::SORT_ORDER => 10,
                RuleInterface::ACTION => RuleInterface::ACTION_HIDE,
                RuleInterface::APPLIES_TO_METHODS => ['flatrate_flatrate'],
                RuleInterface::HIDE_OTHERS => 0,
                RuleInterface::CUSTOMER_GROUP_IDS => [1],
                RuleInterface::STORE_IDS => [],
                RuleInterface::FROM_DATE => '2026-01-01',
                RuleInterface::TO_DATE => null,
                RuleArrayConverter::CONDITIONS_KEY => ['type' => 'combine'],
            ],
            $result
        );
    }

    public function testFromArrayBuildsAnUnsavedRuleWithDefaults(): void
    {
        $conditions = $this->createMock(\Magento\Rule\Model\Condition\Combine::class);
        $conditions->expects(self::never())->method('loadArray');

        $rule = $this->createMock(Rule::class);
        $rule->method('getConditions')->willReturn($conditions);
        $this->ruleFactory->method('create')->willReturn($rule);

        $rule->expects(self::once())->method('setName')->with('Embargo')->willReturnSelf();
        $rule->expects(self::once())->method('setDescription')->with(null)->willReturnSelf();
        $rule->expects(self::once())->method('setIsActive')->with(true)->willReturnSelf();
        $rule->expects(self::once())->method('setSortOrder')->with(0)->willReturnSelf();
        $rule->expects(self::once())->method('setAction')->with(RuleInterface::ACTION_HIDE)->willReturnSelf();
        $rule->expects(self::once())->method('setAppliesToMethods')->with([])->willReturnSelf();
        $rule->expects(self::once())->method('setHideOthers')->with(false)->willReturnSelf();
        $rule->expects(self::once())->method('setCustomerGroupIds')->with([])->willReturnSelf();
        $rule->expects(self::once())->method('setStoreIds')->with([])->willReturnSelf();
        $rule->expects(self::once())->method('setFromDate')->with(null)->willReturnSelf();
        $rule->expects(self::once())->method('setToDate')->with(null)->willReturnSelf();

        $result = $this->converter->fromArray([RuleInterface::NAME => 'Embargo']);

        self::assertSame($rule, $result);
    }

    public function testFromArrayNormalisesCommaStringListsAndLoadsConditions(): void
    {
        $conditions = $this->createMock(\Magento\Rule\Model\Condition\Combine::class);
        $conditions->expects(self::once())
            ->method('setConditions')
            ->with([])
            ->willReturnSelf();
        $conditions->expects(self::once())
            ->method('loadArray')
            ->with(self::validConditions());

        $rule = $this->createMock(Rule::class);
        $rule->method('getConditions')->willReturn($conditions);
        $this->ruleFactory->method('create')->willReturn($rule);
        $rule->method('setName')->willReturnSelf();
        $rule->method('setDescription')->willReturnSelf();
        $rule->method('setIsActive')->willReturnSelf();
        $rule->method('setSortOrder')->willReturnSelf();
        $rule->method('setAction')->willReturnSelf();
        $rule->method('setHideOthers')->willReturnSelf();
        $rule->method('setFromDate')->willReturnSelf();
        $rule->method('setToDate')->willReturnSelf();

        $rule->expects(self::once())
            ->method('setAppliesToMethods')
            ->with(['flatrate_flatrate', 'tablerate_bestway'])
            ->willReturnSelf();
        $rule->expects(self::once())
            ->method('setCustomerGroupIds')
            ->with([1, 2])
            ->willReturnSelf();
        $rule->expects(self::once())
            ->method('setStoreIds')
            ->with([1])
            ->willReturnSelf();

        $this->converter->fromArray([
            RuleInterface::NAME => 'Embargo',
            RuleInterface::APPLIES_TO_METHODS => 'flatrate_flatrate, tablerate_bestway',
            RuleInterface::CUSTOMER_GROUP_IDS => '1,2',
            RuleInterface::STORE_IDS => [1],
            RuleArrayConverter::CONDITIONS_KEY => self::validConditions(),
        ]);
    }

    private static function validConditions(): array
    {
        return [
            'type' => \Magento\SalesRule\Model\Rule\Condition\Combine::class,
            'aggregator' => 'all',
            'value' => '1',
            RuleArrayConverter::CONDITIONS_KEY => [
                [
                    'type' => \Magento\SalesRule\Model\Rule\Condition\Address::class,
                    'attribute' => 'country_id',
                    'operator' => '==',
                    'value' => 'US',
                ],
            ],
        ];
    }

    public static function invalidRules(): array
    {
        return [
            'no name' => [[RuleInterface::NAME => '  ']],
            'unknown action' => [[RuleInterface::NAME => 'X', RuleInterface::ACTION => 'delete_everything']],
            'bad date' => [[RuleInterface::NAME => 'X', RuleInterface::FROM_DATE => '31/12/2026']],
            'impossible date' => [[RuleInterface::NAME => 'X', RuleInterface::TO_DATE => '2026-02-30']],
            'non-numeric group' => [[RuleInterface::NAME => 'X', RuleInterface::CUSTOMER_GROUP_IDS => '1,wholesale']],
            'unreadable conditions' => [[RuleInterface::NAME => 'X', RuleArrayConverter::CONDITIONS_KEY => 'not json']],
            'unknown condition type' => [[RuleInterface::NAME => 'X', RuleArrayConverter::CONDITIONS_KEY => [
                'type' => \Magento\SalesRule\Model\Rule\Condition\Combine::class,
                RuleArrayConverter::CONDITIONS_KEY => [['type' => 'Vendor\\Typo\\Condition']],
            ]]],
            'non-condition class' => [[RuleInterface::NAME => 'X', RuleArrayConverter::CONDITIONS_KEY => [
                'type' => \Magento\SalesRule\Model\Rule\Condition\Combine::class,
                RuleArrayConverter::CONDITIONS_KEY => [['type' => \stdClass::class]],
            ]]],
            'unknown root type' => [[RuleInterface::NAME => 'X', RuleArrayConverter::CONDITIONS_KEY => ['type' => 'combine']]],
            'child without type' => [[RuleInterface::NAME => 'X', RuleArrayConverter::CONDITIONS_KEY => [
                'type' => \Magento\SalesRule\Model\Rule\Condition\Combine::class,
                RuleArrayConverter::CONDITIONS_KEY => [['attribute' => 'country_id', 'value' => 'US']],
            ]]],
        ];
    }

    #[DataProvider('invalidRules')]
    public function testFromArrayRejectsInvalidRulesBeforeBuildingOne(array $data): void
    {
        $this->ruleFactory->expects(self::never())->method('create');

        $this->expectException(LocalizedException::class);
        $this->converter->fromArray($data);
    }
}
