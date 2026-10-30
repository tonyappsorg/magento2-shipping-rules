<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rule\Model\Action\CollectionFactory as ActionCollectionFactory;
use Magento\SalesRule\Model\Rule\Condition\CombineFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Model\Rule;

class RuleTest extends TestCase
{
    private CombineFactory&MockObject $conditionCombineFactory;
    private ActionCollectionFactory&MockObject $actionCollectionFactory;
    private Rule $rule;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $objectManager->prepareObjectManager(
            [
                [ExtensionAttributesFactory::class, $this->createMock(ExtensionAttributesFactory::class)],
                [AttributeValueFactory::class, $this->createMock(AttributeValueFactory::class)],
                [Json::class, new Json()],
            ]
        );

        $this->conditionCombineFactory = $this->createMock(CombineFactory::class);
        $this->actionCollectionFactory = $this->createMock(ActionCollectionFactory::class);

        $this->rule = $objectManager->getObject(
            Rule::class,
            [
                'conditionCombineFactory' => $this->conditionCombineFactory,
                'actionCollectionFactory' => $this->actionCollectionFactory,
            ]
        );
    }

    public function testNameRoundTrips(): void
    {
        $this->rule->setName('Hide freight to embargoed countries');

        self::assertSame('Hide freight to embargoed countries', $this->rule->getName());
    }

    public function testDescriptionIsNullable(): void
    {
        self::assertNull($this->rule->getDescription());

        $this->rule->setDescription('Some notes');

        self::assertSame('Some notes', $this->rule->getDescription());
    }

    public function testIsActiveRoundTrips(): void
    {
        self::assertFalse($this->rule->isActive());

        $this->rule->setIsActive(true);
        self::assertTrue($this->rule->isActive());

        $this->rule->setIsActive(false);
        self::assertFalse($this->rule->isActive());
    }

    public function testSortOrderRoundTrips(): void
    {
        $this->rule->setSortOrder(5);

        self::assertSame(5, $this->rule->getSortOrder());
    }

    public function testActionDefaultsToHideWhenUnset(): void
    {
        self::assertSame(RuleInterface::ACTION_HIDE, $this->rule->getAction());
    }

    public function testActionRoundTrips(): void
    {
        $this->rule->setAction(RuleInterface::ACTION_CONTACT_STORE);

        self::assertSame(RuleInterface::ACTION_CONTACT_STORE, $this->rule->getAction());
    }

    public function testAppliesToMethodsRoundTripsThroughCommaString(): void
    {
        $this->rule->setAppliesToMethods(['flatrate_flatrate', 'tablerate_bestway']);

        self::assertSame('flatrate_flatrate,tablerate_bestway', $this->rule->getData(RuleInterface::APPLIES_TO_METHODS));
        self::assertSame(['flatrate_flatrate', 'tablerate_bestway'], $this->rule->getAppliesToMethods());
    }

    public function testAppliesToMethodsParsesMessyCommaString(): void
    {
        $this->rule->setData(RuleInterface::APPLIES_TO_METHODS, ' flatrate_flatrate ,, tablerate_bestway ,');

        self::assertSame(['flatrate_flatrate', 'tablerate_bestway'], $this->rule->getAppliesToMethods());
    }

    public function testAppliesToMethodsEmptyMeansAllMethods(): void
    {
        self::assertSame([], $this->rule->getAppliesToMethods());
    }

    public function testHideOthersRoundTrips(): void
    {
        self::assertFalse($this->rule->getHideOthers());

        $this->rule->setHideOthers(true);

        self::assertTrue($this->rule->getHideOthers());
    }

    public function testCustomerGroupIdsRoundTripAsIntegers(): void
    {
        $this->rule->setCustomerGroupIds([1, 2, 3]);

        self::assertSame('1,2,3', $this->rule->getData(RuleInterface::CUSTOMER_GROUP_IDS));
        self::assertSame([1, 2, 3], $this->rule->getCustomerGroupIds());
    }

    public function testStoreIdsRoundTrip(): void
    {
        $this->rule->setStoreIds([1, 2]);

        self::assertSame([1, 2], $this->rule->getStoreIds());
    }

    public function testFromDateEmptyStringBecomesNull(): void
    {
        $this->rule->setFromDate('');

        self::assertNull($this->rule->getFromDate());
    }

    public function testFromDateRoundTrips(): void
    {
        $this->rule->setFromDate('2026-01-01');

        self::assertSame('2026-01-01', $this->rule->getFromDate());
    }

    public function testToDateNullPassesThrough(): void
    {
        $this->rule->setToDate(null);

        self::assertNull($this->rule->getToDate());
    }

    public function testBeforeSaveFlattensListFieldsSetAsRawArrays(): void
    {
        $this->givenConditionsAndActionsInstances();

        // Simulates the admin controller's generic $rule->addData($data) path, which bypasses
        // the typed setters and stores multiselect values as plain arrays.
        $this->rule->setData(RuleInterface::APPLIES_TO_METHODS, ['flatrate_flatrate']);
        $this->rule->setData(RuleInterface::CUSTOMER_GROUP_IDS, [1, 2]);
        $this->rule->setData(RuleInterface::STORE_IDS, []);

        $this->rule->beforeSave();

        self::assertSame('flatrate_flatrate', $this->rule->getData(RuleInterface::APPLIES_TO_METHODS));
        self::assertSame('1,2', $this->rule->getData(RuleInterface::CUSTOMER_GROUP_IDS));
        self::assertSame('', $this->rule->getData(RuleInterface::STORE_IDS));
    }

    /**
     * A real (not mocked) DataObject with an explicit asArray(): setRule()/setId()/setPrefix() are
     * magic (DataObject::__call), which PHPUnit mocks cannot stub — only real declared methods.
     */
    private function givenConditionsAndActionsInstances(): void
    {
        $stub = new class extends \Magento\Framework\DataObject {
            public function asArray(array $arrAttributes = []): array
            {
                return [];
            }
        };

        $this->conditionCombineFactory->method('create')->willReturn(clone $stub);
        $this->actionCollectionFactory->method('create')->willReturn(clone $stub);
    }
}
