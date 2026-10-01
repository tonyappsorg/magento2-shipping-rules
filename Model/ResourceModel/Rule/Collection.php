<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\ResourceModel\Rule;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule as RuleResource;
use Tonyapps\ShippingRules\Model\Rule as RuleModel;

/**
 * Shipping rule collection.
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = RuleInterface::RULE_ID;

    protected function _construct()
    {
        $this->_init(RuleModel::class, RuleResource::class);
    }

    /**
     * Restrict the collection to active rules ordered by sort order.
     *
     * @return $this
     */
    public function addActiveFilter(): self
    {
        $this->addFieldToFilter(RuleInterface::IS_ACTIVE, 1);
        $this->setOrder(RuleInterface::SORT_ORDER, self::SORT_ORDER_ASC);
        $this->setOrder(RuleInterface::RULE_ID, self::SORT_ORDER_ASC);

        return $this;
    }
}
