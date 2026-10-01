<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Model\Rule\Io;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\Rule\Io\JsonExporter;
use Tonyapps\ShippingRules\Model\Rule\Io\RuleArrayConverter;

class JsonExporterTest extends TestCase
{
    private RuleArrayConverter&MockObject $converter;
    private JsonExporter $exporter;

    protected function setUp(): void
    {
        $this->converter = $this->createMock(RuleArrayConverter::class);
        $this->exporter = new JsonExporter($this->converter);
    }

    public function testExportSerialisesEveryRuleAsAJsonArray(): void
    {
        $ruleA = $this->createMock(Rule::class);
        $ruleB = $this->createMock(Rule::class);
        $this->converter->method('toArray')->willReturnMap([
            [$ruleA, ['name' => 'A']],
            [$ruleB, ['name' => 'B']],
        ]);

        $json = $this->exporter->export([$ruleA, $ruleB]);

        self::assertSame([['name' => 'A'], ['name' => 'B']], json_decode($json, true));
    }

    public function testExportWithNoRulesProducesAnEmptyArray(): void
    {
        self::assertSame([], json_decode($this->exporter->export([]), true));
    }

    public function testContentTypeAndFileName(): void
    {
        self::assertSame('application/json', $this->exporter->getContentType());
        self::assertSame('shipping_rules.json', $this->exporter->getFileName());
    }
}
