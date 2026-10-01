<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;

/**
 * Option source for the rule "action" field (admin form + validation).
 */
class Action implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => RuleInterface::ACTION_HIDE, 'label' => __('Hide selected shipping methods')],
            ['value' => RuleInterface::ACTION_CONTACT_STORE, 'label' => __('Show "Contact store" method')],
        ];
    }
}
