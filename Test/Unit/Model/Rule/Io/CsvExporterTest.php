<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Model\Rule\Io;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\Rule\Io\CsvExporter;
use Tonyapps\ShippingRules\Model\Rule\Io\RuleArrayConverter;

class CsvExporterTest extends TestCase
{
    private RuleArrayConverter&MockObject $converter;
    private CsvExporter $exporter;

    protected function setUp(): void
    {
        $this->converter = $this->createMock(RuleArrayConverter::class);
        $this->exporter = new CsvExporter($this->converter);
    }

    public function testExportWritesHeaderAndOneRowPerRule(): void
    {
        $rule = $this->createMock(Rule::class);
        $this->converter->method('toArray')->with($rule)->willReturn([
            RuleInterface::NAME => 'Embargo',
            RuleInterface::DESCRIPTION => 'Notes',
            RuleInterface::IS_ACTIVE => 1,
            RuleInterface::SORT_ORDER => 5,
            RuleInterface::ACTION => RuleInterface::ACTION_HIDE,
            RuleInterface::APPLIES_TO_METHODS => ['flatrate_flatrate', 'tablerate_bestway'],
            RuleInterface::HIDE_OTHERS => 0,
            RuleInterface::CUSTOMER_GROUP_IDS => [1, 2],
            RuleInterface::STORE_IDS => [],
            RuleInterface::FROM_DATE => '2026-01-01',
            RuleInterface::TO_DATE => null,
            RuleArrayConverter::CONDITIONS_KEY => ['type' => 'combine'],
        ]);

        $csv = $this->exporter->export([$rule]);
        $rows = array_map(
            fn(string $line): array => str_getcsv($line, escape: ''),
            explode("\n", trim(str_replace("\r\n", "\n", $csv)))
        );

        self::assertSame(CsvExporter::HEADER, $rows[0]);
        self::assertSame(
            [
                'Embargo',
                'Notes',
                '1',
                '5',
                RuleInterface::ACTION_HIDE,
                'flatrate_flatrate,tablerate_bestway',
                '0',
                '1,2',
                '',
                '2026-01-01',
                '',
                '{"type":"combine"}',
            ],
            $rows[1]
        );
    }

    public function testExportDoublesQuotesInsideJsonPerRfc4180(): void
    {
        $rule = $this->createMock(Rule::class);
        $this->converter->method('toArray')->willReturn([
            RuleInterface::NAME => 'Embargo',
            RuleInterface::IS_ACTIVE => 1,
            RuleInterface::SORT_ORDER => 0,
            RuleInterface::ACTION => RuleInterface::ACTION_HIDE,
            RuleInterface::APPLIES_TO_METHODS => [],
            RuleInterface::HIDE_OTHERS => 0,
            RuleInterface::CUSTOMER_GROUP_IDS => [],
            RuleInterface::STORE_IDS => [],
            RuleArrayConverter::CONDITIONS_KEY => ['value' => 'say "hi"'],
        ]);

        $csv = $this->exporter->export([$rule]);

        // JSON {"value":"say \"hi\""}: every quote doubled, the \" is not left as a bare quote.
        self::assertStringContainsString('"{""value"":""say \""hi\""""}"', $csv);
    }

    public function testExportWithNoRulesWritesOnlyTheHeader(): void
    {
        $csv = $this->exporter->export([]);

        self::assertSame(CsvExporter::HEADER, str_getcsv(trim($csv), escape: ''));
    }

    public function testContentTypeAndFileName(): void
    {
        self::assertSame('text/csv', $this->exporter->getContentType());
        self::assertSame('shipping_rules.csv', $this->exporter->getFileName());
    }
}
