<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Model\Rule\Io;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\Rule\Io\CsvImporter;
use Tonyapps\ShippingRules\Model\Rule\Io\RuleArrayConverter;

class CsvImporterTest extends TestCase
{
    private RuleArrayConverter&MockObject $converter;
    private CsvImporter $importer;

    protected function setUp(): void
    {
        $this->converter = $this->createMock(RuleArrayConverter::class);
        $this->importer = new CsvImporter($this->converter);
    }

    public function testImportParsesListFieldsAndConditionsForEachRow(): void
    {
        $csv = implode(',', \Tonyapps\ShippingRules\Model\Rule\Io\CsvExporter::HEADER) . "\n"
            . 'Embargo,Notes,1,5,hide,"flatrate_flatrate,tablerate_bestway",0,"1,2",,2026-01-01,,"{""type"":""combine""}"';

        $rule = $this->createMock(Rule::class);
        $this->converter->expects(self::once())
            ->method('fromArray')
            ->with([
                RuleInterface::NAME => 'Embargo',
                RuleInterface::DESCRIPTION => 'Notes',
                RuleInterface::IS_ACTIVE => '1',
                RuleInterface::SORT_ORDER => '5',
                RuleInterface::ACTION => 'hide',
                RuleInterface::APPLIES_TO_METHODS => ['flatrate_flatrate', 'tablerate_bestway'],
                RuleInterface::HIDE_OTHERS => '0',
                RuleInterface::CUSTOMER_GROUP_IDS => ['1', '2'],
                RuleInterface::STORE_IDS => [],
                RuleInterface::FROM_DATE => '2026-01-01',
                RuleInterface::TO_DATE => '',
                RuleArrayConverter::CONDITIONS_KEY => ['type' => 'combine'],
            ])
            ->willReturn($rule);

        self::assertSame([$rule], $this->importer->import($csv));
    }

    public function testImportSkipsBlankRows(): void
    {
        $csv = implode(',', \Tonyapps\ShippingRules\Model\Rule\Io\CsvExporter::HEADER) . "\n"
            . ",,,,,,,,,,,\n"
            . 'Embargo,,1,0,hide,,0,,,,,';

        $this->converter->method('fromArray')->willReturn($this->createMock(Rule::class));

        self::assertCount(1, $this->importer->import($csv));
    }

    public function testImportRejectsUnreadableConditionsJsonInsteadOfMatchingEveryCart(): void
    {
        $csv = implode(',', \Tonyapps\ShippingRules\Model\Rule\Io\CsvExporter::HEADER) . "\n"
            . 'Embargo,,1,0,hide,,0,,,,,"{""type"":"';
        $this->converter->expects(self::never())->method('fromArray');

        $this->expectException(LocalizedException::class);
        $this->importer->import($csv);
    }

    public function testImportReadsRfc4180QuotesInsideConditionsJson(): void
    {
        $conditions = ['type' => 'combine', 'value' => 'say "hi" \\'];
        $csv = implode(',', \Tonyapps\ShippingRules\Model\Rule\Io\CsvExporter::HEADER) . "\n"
            . 'Embargo,,1,0,hide,,0,,,,,"' . str_replace('"', '""', json_encode($conditions)) . '"';
        $this->converter->expects(self::once())->method('fromArray')
            ->with(self::callback(fn(array $data): bool => $data[RuleArrayConverter::CONDITIONS_KEY] === $conditions))
            ->willReturn($this->createMock(Rule::class));

        $this->importer->import($csv);
    }

    public function testImportStillReadsExportsWrittenWithBackslashEscape(): void
    {
        $conditions = ['type' => 'combine', 'value' => 'say "hi"'];
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, \Tonyapps\ShippingRules\Model\Rule\Io\CsvExporter::HEADER, escape: '\\');
        fputcsv($handle, ['Embargo', '', '1', '0', 'hide', '', '0', '', '', '', '', json_encode($conditions)], escape: '\\');
        rewind($handle);
        $csv = stream_get_contents($handle);
        $this->converter->expects(self::once())->method('fromArray')
            ->with(self::callback(fn(array $data): bool => $data[RuleArrayConverter::CONDITIONS_KEY] === $conditions))
            ->willReturn($this->createMock(Rule::class));

        $this->importer->import($csv);
    }

    public function testImportThrowsOnEmptyFile(): void
    {
        $this->expectException(LocalizedException::class);

        $this->importer->import('');
    }

    public function testImportThrowsWhenNoDataRowsPresent(): void
    {
        $csv = implode(',', \Tonyapps\ShippingRules\Model\Rule\Io\CsvExporter::HEADER);

        $this->expectException(LocalizedException::class);

        $this->importer->import($csv);
    }
}
