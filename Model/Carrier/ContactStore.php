<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Model\RuleEvaluator;

/**
 * Offline "contact store for a quote" carrier.
 *
 * This carrier exposes a single pseudo-method that appears only when an active rule with the
 * "contact_store" action matches the cart. It lets the customer place the order while signalling
 * that shipping will be quoted manually by the store.
 */
class ContactStore extends AbstractCarrier implements CarrierInterface
{
    /**
     * Carrier code — also the rate's carrier/method code (`tonyapps_contactstore_tonyapps_contactstore`).
     */
    public const string CODE = 'tonyapps_contactstore';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var ResultFactory
     */
    private ResultFactory $rateResultFactory;

    /**
     * @var MethodFactory
     */
    private MethodFactory $rateMethodFactory;

    /**
     * @var RuleEvaluator
     */
    private RuleEvaluator $ruleEvaluator;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        RuleEvaluator $ruleEvaluator,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->ruleEvaluator = $ruleEvaluator;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @inheritDoc
     *
     * @return Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        if (!$this->hasMatchingContactStoreRule($request)) {
            return false;
        }

        /** @var Result $result */
        $result = $this->rateResultFactory->create();

        /** @var Method $method */
        $method = $this->rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));

        $price = (float)$this->getConfigData('price');
        $method->setPrice($price);
        $method->setCost($price);

        $result->append($method);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getAllowedMethods(): array
    {
        return [$this->_code => (string)$this->getConfigData('name')];
    }

    /**
     * Whether any active rule asks for the contact-store method for this request.
     */
    private function hasMatchingContactStoreRule(RateRequest $request): bool
    {
        foreach ($this->ruleEvaluator->getMatchingRules($request) as $rule) {
            if ($rule->getAction() === RuleInterface::ACTION_CONTACT_STORE) {
                return true;
            }
        }

        return false;
    }
}
