<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Plugin\Quote;

use Magento\Quote\Model\Quote\Config;
use Magento\SalesRule\Model\ResourceModel\Rule as SalesRuleResource;
use PHPUnit\Framework\TestCase;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule\Collection;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule\CollectionFactory;
use Tonyapps\ShippingRules\Plugin\Quote\ProductAttributesPlugin;

class ProductAttributesPluginTest extends TestCase
{
    public function testAddsAttributesUsedByActiveRulesOnceAndQueriesOnce(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('addActiveFilter')->willReturnSelf();
        $collection->method('getColumnValues')->willReturn([
            '{"type":"Magento\\\\SalesRule\\\\Model\\\\Rule\\\\Condition\\\\Product","attribute":"color"}',
            '{"type":"Magento\\\\SalesRule\\\\Model\\\\Rule\\\\Condition\\\\Product","attribute":"sku"}'
            . '{"type":"Magento\\\\SalesRule\\\\Model\\\\Rule\\\\Condition\\\\Product","attribute":"color"}',
        ]);
        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects(self::once())->method('create')->willReturn($collection);

        // The real core parser: the point is that it understands our serialized conditions.
        $salesRuleResource = (new \ReflectionClass(SalesRuleResource::class))->newInstanceWithoutConstructor();
        $plugin = new ProductAttributesPlugin($factory, $salesRuleResource);
        $config = $this->createMock(Config::class);

        self::assertSame(['name', 'sku', 'color'], $plugin->afterGetProductAttributes($config, ['name', 'sku']));
        self::assertSame(['name', 'sku', 'color'], $plugin->afterGetProductAttributes($config, ['name', 'sku']));
    }
}
