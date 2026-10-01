<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Model\Rule\Io;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\Rule\Io\JsonImporter;
use Tonyapps\ShippingRules\Model\Rule\Io\RuleArrayConverter;

class JsonImporterTest extends TestCase
{
    private RuleArrayConverter&MockObject $converter;
    private JsonImporter $importer;

    protected function setUp(): void
    {
        $this->converter = $this->createMock(RuleArrayConverter::class);
        $this->importer = new JsonImporter($this->converter);
    }

    public function testImportConvertsEachArrayElementToARule(): void
    {
        $ruleA = $this->createMock(Rule::class);
        $ruleB = $this->createMock(Rule::class);
        $this->converter->method('fromArray')->willReturnMap([
            [['name' => 'A'], $ruleA],
            [['name' => 'B'], $ruleB],
        ]);

        $rules = $this->importer->import(json_encode([['name' => 'A'], ['name' => 'B']]));

        self::assertSame([$ruleA, $ruleB], $rules);
    }

    public function testImportIgnoresNonArrayElements(): void
    {
        $rule = $this->createMock(Rule::class);
        $this->converter->method('fromArray')->willReturn($rule);

        $rules = $this->importer->import(json_encode([['name' => 'A'], 'not-a-rule', 42]));

        self::assertSame([$rule], $rules);
    }

    public function testImportThrowsOnInvalidJson(): void
    {
        $this->expectException(LocalizedException::class);

        $this->importer->import('{not valid json');
    }

    public function testImportThrowsWhenTopLevelJsonIsNotAnArray(): void
    {
        $this->expectException(LocalizedException::class);

        $this->importer->import(json_encode('just a string'));
    }

    public function testImportThrowsWhenNoRulesWereFound(): void
    {
        $this->expectException(LocalizedException::class);

        $this->importer->import(json_encode(['not-a-rule']));
    }
}
