<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Api\Data;

interface RuleInterface
{
    public const string RULE_ID = 'rule_id';
    public const string NAME = 'name';
    public const string DESCRIPTION = 'description';
    public const string IS_ACTIVE = 'is_active';
    public const string SORT_ORDER = 'sort_order';
    public const string ACTION = 'action';
    public const string APPLIES_TO_METHODS = 'applies_to_methods';
    public const string HIDE_OTHERS = 'hide_others';
    public const string CUSTOMER_GROUP_IDS = 'customer_group_ids';
    public const string STORE_IDS = 'store_ids';
    public const string FROM_DATE = 'from_date';
    public const string TO_DATE = 'to_date';

    public const string ACTION_HIDE = 'hide';
    public const string ACTION_CONTACT_STORE = 'contact_store';

    public function getName(): ?string;

    public function setName(string $name): self;

    public function getDescription(): ?string;

    public function setDescription(?string $description): self;

    public function isActive(): bool;

    public function setIsActive(bool $isActive): self;

    public function getSortOrder(): int;

    public function setSortOrder(int $sortOrder): self;

    public function getAction(): string;

    public function setAction(string $action): self;

    public function getAppliesToMethods(): array;

    public function setAppliesToMethods(array $methods): self;

    public function getHideOthers(): bool;

    public function setHideOthers(bool $hideOthers): self;

    public function getCustomerGroupIds(): array;

    public function setCustomerGroupIds(array $groupIds): self;

    public function getStoreIds(): array;

    public function setStoreIds(array $storeIds): self;

    public function getFromDate(): ?string;

    public function setFromDate(?string $fromDate): self;

    public function getToDate(): ?string;

    public function setToDate(?string $toDate): self;

}
