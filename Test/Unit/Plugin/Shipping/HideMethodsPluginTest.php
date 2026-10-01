<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Plugin\Shipping;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Shipping;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Model\Carrier\ContactStore;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\RuleEvaluator;
use Tonyapps\ShippingRules\Plugin\Shipping\HideMethodsPlugin;

class HideMethodsPluginTest extends TestCase
{
    private RuleEvaluator&MockObject $evaluator;
    private Shipping&MockObject $shipping;
    private Result&MockObject $rateResult;
    private RateRequest&MockObject $request;
    private HideMethodsPlugin $plugin;

    /**
     * @var DataObject[]
     */
    private array $appended = [];

    protected function setUp(): void
    {
        $this->evaluator = $this->createMock(RuleEvaluator::class);
        $this->shipping = $this->createMock(Shipping::class);
        $this->rateResult = $this->createMock(Result::class);
        $this->request = $this->createMock(RateRequest::class);
        $this->plugin = new HideMethodsPlugin($this->evaluator);
        $this->appended = [];
    }

    public function testNoMatchingRulesLeavesResultUntouched(): void
    {
        $this->evaluator->method('getMatchingRules')->willReturn([]);
        $this->shipping->expects(self::never())->method('getResult');

        $result = $this->plugin->afterCollectRates($this->shipping, $this->shipping, $this->request);

        self::assertSame($this->shipping, $result);
    }

    public function testHideRuleRemovesTargetedMethodOnly(): void
    {
        $flatrate = $this->rate('flatrate', 'flatrate');
        $tablerate = $this->rate('tablerate', 'bestway');

        $this->givenRates([$flatrate, $tablerate]);
        $this->givenMatchingRules([$this->rule(RuleInterface::ACTION_HIDE, ['flatrate_flatrate'], false)]);
        $this->expectRebuild();

        $this->plugin->afterCollectRates($this->shipping, $this->shipping, $this->request);

        self::assertSame([$tablerate], $this->appended);
    }

    public function testHideRuleWithoutTargetsHidesEverything(): void
    {
        $this->givenRates([$this->rate('flatrate', 'flatrate'), $this->rate('tablerate', 'bestway')]);
        $this->givenMatchingRules([$this->rule(RuleInterface::ACTION_HIDE, [], false)]);
        $this->expectRebuild();

        $this->plugin->afterCollectRates($this->shipping, $this->shipping, $this->request);

        self::assertSame([], $this->appended);
    }

    public function testContactStoreHideOthersKeepsOnlyContactStore(): void
    {
        $flatrate = $this->rate('flatrate', 'flatrate');
        $contact = $this->rate(ContactStore::CODE, ContactStore::CODE);

        $this->givenRates([$flatrate, $contact]);
        $this->givenMatchingRules([$this->rule(RuleInterface::ACTION_CONTACT_STORE, [], true)]);
        $this->expectRebuild();

        $this->plugin->afterCollectRates($this->shipping, $this->shipping, $this->request);

        self::assertSame([$contact], $this->appended);
    }

    /**
     * @param DataObject[] $rates
     */
    private function givenRates(array $rates): void
    {
        $this->shipping->method('getResult')->willReturn($this->rateResult);
        $this->rateResult->method('getAllRates')->willReturn($rates);
    }

    /**
     * @param array<int, Rule&MockObject> $rules
     */
    private function givenMatchingRules(array $rules): void
    {
        $this->evaluator->method('getMatchingRules')->willReturn($rules);
    }

    private function expectRebuild(): void
    {
        $this->rateResult->expects(self::once())->method('reset')->willReturnSelf();
        $this->rateResult->method('append')->willReturnCallback(
            function (DataObject $rate): Result {
                $this->appended[] = $rate;

                return $this->rateResult;
            }
        );
    }

    private function rate(string $carrier, string $method): DataObject
    {
        return new DataObject(['carrier' => $carrier, 'method' => $method]);
    }

    /**
     * @param string[] $methods
     * @return Rule&MockObject
     */
    private function rule(string $action, array $methods, bool $hideOthers): Rule
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getAction')->willReturn($action);
        $rule->method('getAppliesToMethods')->willReturn($methods);
        $rule->method('getHideOthers')->willReturn($hideOthers);

        return $rule;
    }
}
