<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Model\Source;

use PHPUnit\Framework\TestCase;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Model\Source\Action;

class ActionTest extends TestCase
{
    private Action $source;

    protected function setUp(): void
    {
        $this->source = new Action();
    }

    public function testOptionArrayExposesBothActions(): void
    {
        $options = $this->source->toOptionArray();
        $values = array_column($options, 'value');

        self::assertContains(RuleInterface::ACTION_HIDE, $values);
        self::assertContains(RuleInterface::ACTION_CONTACT_STORE, $values);
        self::assertCount(2, $options);
    }
}
