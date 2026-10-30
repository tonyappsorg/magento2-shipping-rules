<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Api\RuleRepositoryInterface;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule as RuleResource;

/**
 * Default shipping rule repository.
 */
class RuleRepository implements RuleRepositoryInterface
{
    public function __construct(
        private readonly RuleResource $resource,
        private readonly RuleFactory $ruleFactory
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

}
