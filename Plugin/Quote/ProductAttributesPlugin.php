<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Plugin\Quote;

use Magento\Quote\Model\Quote\Config;
use Magento\SalesRule\Model\ResourceModel\Rule as SalesRuleResource;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule\CollectionFactory;

/**
 * Quote items only load the product attributes core asks for (plus those used by cart price rules), so a shipping
 * rule condition on e.g. "color" would always see an empty value. Adds the attributes our active rules use.
 *
 * ponytail: reads the active rules once per request; store the codes on save (like salesrule_product_attribute)
 * if rule counts ever make that query matter.
 */
class ProductAttributesPlugin
{
    private ?array $codes = null;

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly SalesRuleResource $salesRuleResource
    ) {
    }

    public function afterGetProductAttributes(Config $subject, array $attributeKeys): array
    {
        if ($this->codes === null) {
            $codes = [];
            $serialized = $this->collectionFactory->create()->addActiveFilter()->getColumnValues('conditions_serialized');
            foreach ($serialized as $conditions) {
                // Our conditions are core's cart price rule conditions, so core's own parser applies.
                $codes[] = $this->salesRuleResource->getProductAttributes((string)$conditions);
            }
            $this->codes = array_values(array_unique(array_merge([], ...$codes)));
        }

        return array_values(array_unique(array_merge($attributeKeys, $this->codes)));
    }
}
