<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;

/**
 * Shipping rule resource model.
 */
class Rule extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('tonyapps_shipping_rule', RuleInterface::RULE_ID);
    }
}
