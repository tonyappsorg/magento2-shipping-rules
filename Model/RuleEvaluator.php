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
 * both agree on which rules match. Results are memoised per rate request: the carrier and the plugin get the same
 * request object within one collectRates() run, while a later run (changed cart, new address) gets a new one.
 */
class RuleEvaluator
{
    /**
     * @var \WeakMap<RateRequest, Rule[]>
     */
    private \WeakMap $matchedCache;

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly TimezoneInterface $localeDate,
        private readonly LoggerInterface $logger
    ) {
        $this->matchedCache = new \WeakMap();
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

        if (isset($this->matchedCache[$request])) {
            return $this->matchedCache[$request];
        }

        $storeId = (int)$quote->getStoreId();
        $customerGroupId = (int)$quote->getCustomerGroupId();
        $today = $this->localeDate->date()->format('Y-m-d');

        $matched = [];
        /** @var Rule $rule */
        foreach ($this->collectionFactory->create()->addActiveFilter() as $rule) {
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

        $this->matchedCache[$request] = $matched;

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
