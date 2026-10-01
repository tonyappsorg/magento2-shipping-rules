<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Api\RuleRepositoryInterface;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule as RuleResource;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule\CollectionFactory;

/**
 * Default shipping rule repository.
 */
class RuleRepository implements RuleRepositoryInterface
{
    public function __construct(
        private readonly RuleResource $resource,
        private readonly RuleFactory $ruleFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    public function save(RuleInterface $rule): RuleInterface
    {
        try {
            /** @var Rule $rule */
            $this->resource->save($rule);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the shipping rule: %1', $exception->getMessage()),
                $exception
            );
        }

        return $rule;
    }

    public function getById(int $ruleId): RuleInterface
    {
        /** @var Rule $rule */
        $rule = $this->ruleFactory->create();
        $this->resource->load($rule, $ruleId);
        if (!$rule->getId()) {
            throw new NoSuchEntityException(__('Shipping rule with id "%1" does not exist.', $ruleId));
        }

        return $rule;
    }

    public function delete(RuleInterface $rule): bool
    {
        try {
            /** @var Rule $rule */
            $this->resource->delete($rule);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the shipping rule: %1', $exception->getMessage()),
                $exception
            );
        }

        return true;
    }

    public function deleteById(int $ruleId): bool
    {
        return $this->delete($this->getById($ruleId));
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var SearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
