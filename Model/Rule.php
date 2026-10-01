<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model;

use Magento\Framework\Data\FormFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Rule\Model\Action\CollectionFactory as ActionCollectionFactory;
use Magento\SalesRule\Model\Rule\Condition\CombineFactory;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;

/**
 * Shipping rule model.
 *
 * Extends the native rule engine (Magento\Rule\Model\AbstractModel) so the AND/OR condition tree
 * is serialized/hydrated automatically via the conditions_serialized column. Conditions reuse the
 * Cart Price Rule condition set (product attributes + cart/address attributes).
 *
 * @method \Tonyapps\ShippingRules\Model\ResourceModel\Rule getResource()
 */
class Rule extends \Magento\Rule\Model\AbstractModel implements RuleInterface
{
    /**
     * @var CombineFactory
     */
    private $conditionCombineFactory;

    /**
     * @var ActionCollectionFactory
     */
    private $actionCollectionFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        TimezoneInterface $localeDate,
        CombineFactory $conditionCombineFactory,
        ActionCollectionFactory $actionCollectionFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->conditionCombineFactory = $conditionCombineFactory;
        $this->actionCollectionFactory = $actionCollectionFactory;
        parent::__construct($context, $registry, $formFactory, $localeDate, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init(ResourceModel\Rule::class);
    }

    /**
     * @inheritDoc
     */
    public function getConditionsInstance()
    {
        return $this->conditionCombineFactory->create();
    }

    /**
     * @inheritDoc
     *
     * Actions are unused by this module (rules match on conditions only), but the abstract engine
     * requires an instance. An empty action collection keeps serialization happy.
     */
    public function getActionsInstance()
    {
        return $this->actionCollectionFactory->create();
    }

    /**
     * Field set id used by the admin conditions widget and its NewConditionHtml AJAX endpoint.
     */
    public function getConditionsFieldSetId($formName = ''): string
    {
        return $formName . 'rule_conditions_fieldset_' . (string)$this->getId();
    }

    /**
     * Persist list-type fields as comma-separated strings.
     *
     * The parent beforeSave() serializes the condition tree and normalizes customer_group_ids to
     * an array; since our columns are plain TEXT we flatten every list field back to a string.
     *
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();

        foreach ([self::APPLIES_TO_METHODS, self::CUSTOMER_GROUP_IDS, self::STORE_IDS] as $field) {
            $value = $this->getData($field);
            if (is_array($value)) {
                $this->setData($field, implode(',', $value));
            }
        }

        return $this;
    }

    public function getRuleId(): ?int
    {
        $id = $this->getData(self::RULE_ID);

        return $id === null ? null : (int)$id;
    }

    public function setRuleId(int $ruleId): RuleInterface
    {
        return $this->setData(self::RULE_ID, $ruleId);
    }

    public function getName(): ?string
    {
        $name = $this->getData(self::NAME);

        return $name === null ? null : (string)$name;
    }

    public function setName(string $name): RuleInterface
    {
        return $this->setData(self::NAME, $name);
    }

    public function getDescription(): ?string
    {
        $description = $this->getData(self::DESCRIPTION);

        return $description === null ? null : (string)$description;
    }

    public function setDescription(?string $description): RuleInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    public function isActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $isActive): RuleInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive ? 1 : 0);
    }

    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    public function setSortOrder(int $sortOrder): RuleInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function getAction(): string
    {
        return (string)($this->getData(self::ACTION) ?: self::ACTION_HIDE);
    }

    public function setAction(string $action): RuleInterface
    {
        return $this->setData(self::ACTION, $action);
    }

    public function getAppliesToMethods(): array
    {
        return $this->explodeList($this->getData(self::APPLIES_TO_METHODS));
    }

    public function setAppliesToMethods(array $methods): RuleInterface
    {
        return $this->setData(self::APPLIES_TO_METHODS, implode(',', $methods));
    }

    public function getHideOthers(): bool
    {
        return (bool)$this->getData(self::HIDE_OTHERS);
    }

    public function setHideOthers(bool $hideOthers): RuleInterface
    {
        return $this->setData(self::HIDE_OTHERS, $hideOthers ? 1 : 0);
    }

    public function getCustomerGroupIds(): array
    {
        return array_map('intval', $this->explodeList($this->getData(self::CUSTOMER_GROUP_IDS)));
    }

    public function setCustomerGroupIds(array $groupIds): RuleInterface
    {
        return $this->setData(self::CUSTOMER_GROUP_IDS, implode(',', $groupIds));
    }

    public function getStoreIds(): array
    {
        return array_map('intval', $this->explodeList($this->getData(self::STORE_IDS)));
    }

    public function setStoreIds(array $storeIds): RuleInterface
    {
        return $this->setData(self::STORE_IDS, implode(',', $storeIds));
    }

    public function getFromDate(): ?string
    {
        $value = $this->getData(self::FROM_DATE);

        return $value === null || $value === '' ? null : (string)$value;
    }

    public function setFromDate(?string $fromDate): RuleInterface
    {
        return $this->setData(self::FROM_DATE, $fromDate);
    }

    public function getToDate(): ?string
    {
        $value = $this->getData(self::TO_DATE);

        return $value === null || $value === '' ? null : (string)$value;
    }

    public function setToDate(?string $toDate): RuleInterface
    {
        return $this->setData(self::TO_DATE, $toDate);
    }

    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);

        return $value === null ? null : (string)$value;
    }

    public function getUpdatedAt(): ?string
    {
        $value = $this->getData(self::UPDATED_AT);

        return $value === null ? null : (string)$value;
    }

    /**
     * Split a comma-separated string (or pass through an array) into a clean list of values.
     *
     * @param mixed $value
     * @return string[]
     */
    private function explodeList($value): array
    {
        if (is_array($value)) {
            return array_values(
                array_filter($value, static fn($item): bool => $item !== '' && $item !== null)
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
}
