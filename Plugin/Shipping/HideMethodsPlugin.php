<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Plugin\Shipping;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\AbstractResult;
use Magento\Quote\Model\Quote\Address\RateResult\Error;
use Magento\Shipping\Model\Shipping;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Model\Carrier\ContactStore;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\RuleEvaluator;

/**
 * Removes shipping methods from the collected rate result according to matching rules.
 *
 * Runs after Shipping::collectRates (which already ran every carrier, including our own
 * ContactStore carrier). "hide" rules drop their targeted methods; a "contact_store" rule with
 * "hide others" enabled drops every real method so only the contact-store method survives, but only
 * when that method really is in the result: with the carrier off or country-restricted, hiding the
 * rest would leave the customer nothing to check out with.
 */
readonly class HideMethodsPlugin
{
    public function __construct(
        private RuleEvaluator $ruleEvaluator
    ) {
    }

    /**
     * @param Shipping $subject
     * @param Shipping $result The subject itself — collectRates() returns $this.
     * @return Shipping
     */
    public function afterCollectRates(Shipping $subject, $result, RateRequest $request)
    {
        $rules = $this->ruleEvaluator->getMatchingRules($request);
        if ($rules === []) {
            return $result;
        }

        $rateResult = $subject->getResult();
        if ($rateResult === null) {
            return $result;
        }

        $allRates = $rateResult->getAllRates();
        if ($allRates === []) {
            return $result;
        }

        $hasContactStoreRate = false;
        foreach ($allRates as $rate) {
            if ($rate->getCarrier() === ContactStore::CODE && !$rate instanceof Error) {
                $hasContactStoreRate = true;
                break;
            }
        }

        [$hideAll, $hiddenCodes] = $this->resolveHidden($rules, $hasContactStoreRate);
        if (!$hideAll && $hiddenCodes === []) {
            return $result;
        }

        $kept = [];
        foreach ($allRates as $rate) {
            if ($this->shouldKeep($rate, $hideAll, $hiddenCodes)) {
                $kept[] = $rate;
            }
        }

        if (count($kept) === count($allRates)) {
            return $result;
        }

        $rateResult->reset();
        foreach ($kept as $rate) {
            $rateResult->append($rate);
        }

        return $result;
    }

    /**
     * Compute what to hide from the matching rules.
     *
     * @param Rule[] $rules
     * @return array{0: bool, 1: array<string, bool>} [$hideAll, $hiddenCodes]
     */
    private function resolveHidden(array $rules, bool $hasContactStoreRate): array
    {
        $hideAll = false;
        $hiddenCodes = [];

        foreach ($rules as $rule) {
            $action = $rule->getAction();

            if ($action === RuleInterface::ACTION_HIDE) {
                $targets = $rule->getAppliesToMethods();
                if ($targets === []) {
                    // Empty target list means "all methods".
                    $hideAll = true;
                    continue;
                }
                foreach ($targets as $code) {
                    $hiddenCodes[$code] = true;
                }
            } elseif ($action === RuleInterface::ACTION_CONTACT_STORE && $rule->getHideOthers() && $hasContactStoreRate) {
                $hideAll = true;
            }
        }

        return [$hideAll, $hiddenCodes];
    }

    /**
     * Decide whether a single rate survives the filter. Our own contact-store method is never
     * dropped, so a "hide others" rule cannot hide the very method it is meant to surface.
     *
     * @param AbstractResult $rate
     * @param array<string, bool> $hiddenCodes
     */
    private function shouldKeep($rate, bool $hideAll, array $hiddenCodes): bool
    {
        if ($rate->getCarrier() === ContactStore::CODE) {
            return true;
        }

        if ($hideAll) {
            return false;
        }

        $code = $rate->getCarrier() . '_' . $rate->getMethod();

        return !isset($hiddenCodes[$code]);
    }
}
