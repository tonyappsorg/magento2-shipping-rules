<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\Rule\Io;

use Tonyapps\ShippingRules\Model\Rule;

/**
 * Parses a serialized rule set into unsaved rule models.
 */
interface ImporterInterface
{
    /**
     * @return Rule[] unsaved rule models
     * @throws \Magento\Framework\Exception\LocalizedException on malformed input
     */
    public function import(string $content): array;
}
