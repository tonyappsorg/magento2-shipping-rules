<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\Rule\Io;

use Magento\Framework\Exception\LocalizedException;
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
     * @throws LocalizedException when the data isn't a usable rule, so a bad file imports nothing
     */
    public function fromArray(array $data): Rule
    {
        $this->validate($data);

        /** @var Rule $rule */
        $rule = $this->ruleFactory->create();
        $rule->setName((string)($data[RuleInterface::NAME] ?? ''));
        $rule->setDescription(isset($data[RuleInterface::DESCRIPTION]) ? (string)$data[RuleInterface::DESCRIPTION] : null);
        $rule->setIsActive((bool)($data[RuleInterface::IS_ACTIVE] ?? true));
        $rule->setSortOrder((int)($data[RuleInterface::SORT_ORDER] ?? 0));
        $rule->setAction((string)($data[RuleInterface::ACTION] ?? RuleInterface::ACTION_HIDE));
        $rule->setAppliesToMethods(Rule::normalizeList($data[RuleInterface::APPLIES_TO_METHODS] ?? []));
        $rule->setHideOthers((bool)($data[RuleInterface::HIDE_OTHERS] ?? false));
        $rule->setCustomerGroupIds(array_map('intval', Rule::normalizeList($data[RuleInterface::CUSTOMER_GROUP_IDS] ?? [])));
        $rule->setStoreIds(array_map('intval', Rule::normalizeList($data[RuleInterface::STORE_IDS] ?? [])));
        $rule->setFromDate($this->nullableString($data[RuleInterface::FROM_DATE] ?? null));
        $rule->setToDate($this->nullableString($data[RuleInterface::TO_DATE] ?? null));

        $conditions = $data[self::CONDITIONS_KEY] ?? null;
        if (is_array($conditions) && $conditions !== []) {
            $rule->getConditions()->setConditions([])->loadArray($conditions);
        }

        return $rule;
    }

    /**
     * @param array<string, mixed> $data
     * @throws LocalizedException
     */
    private function validate(array $data): void
    {
        $name = trim((string)($data[RuleInterface::NAME] ?? ''));
        if ($name === '') {
            throw new LocalizedException(__('Every rule needs a name.'));
        }
        $action = (string)($data[RuleInterface::ACTION] ?? '');
        if ($action !== '' && !in_array($action, [RuleInterface::ACTION_HIDE, RuleInterface::ACTION_CONTACT_STORE], true)) {
            throw new LocalizedException(__('Rule "%1" has an unknown action "%2".', $name, $action));
        }
        foreach ([RuleInterface::FROM_DATE, RuleInterface::TO_DATE] as $field) {
            $date = $this->nullableString($data[$field] ?? null);
            if ($date !== null && (\DateTime::createFromFormat('!Y-m-d', $date) ?: null)?->format('Y-m-d') !== $date) {
                throw new LocalizedException(__('Rule "%1" has an invalid %2 "%3"; use YYYY-MM-DD.', $name, $field, $date));
            }
        }
        foreach ([RuleInterface::CUSTOMER_GROUP_IDS, RuleInterface::STORE_IDS] as $field) {
            foreach (Rule::normalizeList($data[$field] ?? []) as $id) {
                if (!ctype_digit($id)) {
                    throw new LocalizedException(__('Rule "%1" has a non-numeric %2 "%3".', $name, $field, $id));
                }
            }
        }
        $conditions = $data[self::CONDITIONS_KEY] ?? null;
        if ($conditions !== null && $conditions !== '' && $conditions !== [] && !is_array($conditions)) {
            throw new LocalizedException(__('Rule "%1" has conditions that could not be read.', $name));
        }
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
