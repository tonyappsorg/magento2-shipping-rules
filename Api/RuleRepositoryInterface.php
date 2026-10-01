<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;

interface RuleRepositoryInterface
{
    public function save(RuleInterface $rule): RuleInterface;
    public function getById(int $ruleId): RuleInterface;
    public function delete(RuleInterface $rule): bool;
    public function deleteById(int $ruleId): bool;
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;
}
