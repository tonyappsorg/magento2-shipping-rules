<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\Rule\Io;

use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\RuleFactory;

/**
 * Single source of truth for converting a rule to/from a plain array.
 *
 * The condition tree is kept as its native asArray() structure so it round-trips losslessly
 * through both JSON and CSV. Rule IDs are intentionally omitted: imports always create new rules.
 */
class RuleArrayConverter
{
    /**
     * Field order used by the array/CSV representation.
     */
    public const FIELDS = [
        RuleInterface::NAME,
        RuleInterface::DESCRIPTION,
        RuleInterface::IS_ACTIVE,
        RuleInterface::SORT_ORDER,
        RuleInterface::ACTION,
        RuleInterface::APPLIES_TO_METHODS,
        RuleInterface::HIDE_OTHERS,
        RuleInterface::CUSTOMER_GROUP_IDS,
        RuleInterface::STORE_IDS,
        RuleInterface::FROM_DATE,
        RuleInterface::TO_DATE,
    ];

    /**
     * Key holding the serialized condition tree in the array/CSV representation.
     */
    public const CONDITIONS_KEY = 'conditions';

    public function __construct(
        private readonly RuleFactory $ruleFactory
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Rule $rule): array
    {
        return [
            RuleInterface::NAME => (string)$rule->getName(),
            RuleInterface::DESCRIPTION => $rule->getDescription(),
            RuleInterface::IS_ACTIVE => $rule->isActive() ? 1 : 0,
            RuleInterface::SORT_ORDER => $rule->getSortOrder(),
            RuleInterface::ACTION => $rule->getAction(),
            RuleInterface::APPLIES_TO_METHODS => $rule->getAppliesToMethods(),
            RuleInterface::HIDE_OTHERS => $rule->getHideOthers() ? 1 : 0,
            RuleInterface::CUSTOMER_GROUP_IDS => $rule->getCustomerGroupIds(),
            RuleInterface::STORE_IDS => $rule->getStoreIds(),
            RuleInterface::FROM_DATE => $rule->getFromDate(),
            RuleInterface::TO_DATE => $rule->getToDate(),
            self::CONDITIONS_KEY => $rule->getConditions()->asArray(),
        ];
    }

    /**
     * Build an unsaved rule from an array representation.
     *
     * @param array<string, mixed> $data
     */
    public function fromArray(array $data): Rule
    {
        /** @var Rule $rule */
        $rule = $this->ruleFactory->create();
        $rule->setName((string)($data[RuleInterface::NAME] ?? ''));
        $rule->setDescription(isset($data[RuleInterface::DESCRIPTION]) ? (string)$data[RuleInterface::DESCRIPTION] : null);
        $rule->setIsActive((bool)($data[RuleInterface::IS_ACTIVE] ?? true));
        $rule->setSortOrder((int)($data[RuleInterface::SORT_ORDER] ?? 0));
        $rule->setAction((string)($data[RuleInterface::ACTION] ?? RuleInterface::ACTION_HIDE));
        $rule->setAppliesToMethods($this->toList($data[RuleInterface::APPLIES_TO_METHODS] ?? []));
        $rule->setHideOthers((bool)($data[RuleInterface::HIDE_OTHERS] ?? false));
        $rule->setCustomerGroupIds(array_map('intval', $this->toList($data[RuleInterface::CUSTOMER_GROUP_IDS] ?? [])));
        $rule->setStoreIds(array_map('intval', $this->toList($data[RuleInterface::STORE_IDS] ?? [])));
        $rule->setFromDate($this->nullableString($data[RuleInterface::FROM_DATE] ?? null));
        $rule->setToDate($this->nullableString($data[RuleInterface::TO_DATE] ?? null));

        $conditions = $data[self::CONDITIONS_KEY] ?? null;
        if (is_array($conditions) && $conditions !== []) {
            $rule->getConditions()->setConditions([])->loadArray($conditions);
        }

        return $rule;
    }

    /**
     * Normalise a value that may be an array or a comma-separated string into a list of strings.
     *
     * @param mixed $value
     * @return string[]
     */
    private function toList($value): array
    {
        if (is_array($value)) {
            return array_values(
                array_filter(
                    array_map(static fn($item): string => trim((string)$item), $value),
                    static fn(string $item): bool => $item !== ''
                )
            );
        }

        if ($value === null || $value === '') {
            return [];
        }

        return array_values(
            array_filter(
                array_map('trim', explode(',', (string)$value)),
                static fn(string $item): bool => $item !== ''
            )
        );
    }

    /**
     * @param mixed $value
     */
    private function nullableString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string)$value;
    }
}
