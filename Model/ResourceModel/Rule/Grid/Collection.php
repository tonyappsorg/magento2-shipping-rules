<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\ResourceModel\Rule\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Grid (UI listing) collection for shipping rules.
 *
 * mainTable / resourceModel are supplied via di.xml so this stays a thin SearchResult.
 */
class Collection extends SearchResult implements SearchResultInterface
{
}
