<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Psr\Log\LoggerInterface;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule\CollectionFactory;

/**
 * Evaluates shipping rules against a rate request.
 *
 * A single evaluation point shared by the hide-methods plugin and the contact-store carrier so
 * both agree on which rules match. Results are memoised per shipping address for the duration of
 * the request (the address is the same object across the carrier and plugin calls).
 */
class RuleEvaluator
{
    /**
     * @var array<int, Rule[]>
     */
    private array $matchedCache = [];

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly TimezoneInterface $localeDate,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Return the active rules whose scope + conditions match the given rate request.
     *
     * @return Rule[]
     */
    public function getMatchingRules(RateRequest $request): array
    {
        $context = $this->resolveContext($request);
        if ($context === null) {
            return [];
        }

        [$quote, $address] = $context;

        $cacheKey = spl_object_id($address);
        if (isset($this->matchedCache[$cacheKey])) {
            return $this->matchedCache[$cacheKey];
        }

        $storeId = (int)$quote->getStoreId();
        $customerGroupId = (int)$quote->getCustomerGroupId();
        $today = $this->localeDate->date()->format('Y-m-d');

        $matched = [];
        /** @var Rule $rule */
        foreach ($this->loadActiveRules() as $rule) {
            if (!$this->matchesScope($rule, $storeId, $customerGroupId, $today)) {
                continue;
            }

            try {
                if ($rule->getConditions()->validate($address)) {
                    $matched[] = $rule;
                }
            } catch (\Throwable $exception) {
                // A broken condition tree must never take down the checkout rate calculation.
                $this->logger->error(
                    sprintf(
                        'Tonyapps_ShippingRules: failed to evaluate rule #%s: %s',
                        (string)$rule->getId(),
                        $exception->getMessage()
                    )
                );
            }
        }

        $this->matchedCache[$cacheKey] = $matched;

        return $matched;
    }

    /**
     * Check store / customer group / date scoping (everything outside the condition tree).
     */
    private function matchesScope(Rule $rule, int $storeId, int $customerGroupId, string $today): bool
    {
        $storeIds = $rule->getStoreIds();
        if ($storeIds !== [] && !in_array($storeId, $storeIds, true)) {
            return false;
        }

        $groupIds = $rule->getCustomerGroupIds();
        if ($groupIds !== [] && !in_array($customerGroupId, $groupIds, true)) {
            return false;
        }

        $fromDate = $rule->getFromDate();
        if ($fromDate !== null && $today < $fromDate) {
            return false;
        }

        $toDate = $rule->getToDate();
        if ($toDate !== null && $today > $toDate) {
            return false;
        }

        return true;
    }

    /**
     * Load active rules ordered by sort order (cached collection factory yields fresh models,
     * but the small per-address memoisation above prevents repeated condition validation).
     *
     * @return \Tonyapps\ShippingRules\Model\ResourceModel\Rule\Collection
     */
    private function loadActiveRules()
    {
        return $this->collectionFactory->create()->addActiveFilter();
    }

    /**
     * Resolve the quote + destination address backing a rate request.
     *
     * @return array{0: Quote, 1: Address}|null
     */
    private function resolveContext(RateRequest $request): ?array
    {
        $items = $request->getAllItems();
        if (empty($items)) {
            return null;
        }

        $firstItem = reset($items);
        if (!$firstItem instanceof AbstractItem) {
            return null;
        }

        $quote = $firstItem->getQuote();
        $address = $firstItem->getAddress();

        if ($address === null && $quote instanceof Quote) {
            $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
        }

        if (!$quote instanceof Quote || !$address instanceof Address) {
            return null;
        }

        return [$quote, $address];
    }
}
