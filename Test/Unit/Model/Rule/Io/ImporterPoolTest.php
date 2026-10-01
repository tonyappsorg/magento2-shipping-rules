<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Model\Rule\Io;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Tonyapps\ShippingRules\Model\Rule\Io\ImporterInterface;
use Tonyapps\ShippingRules\Model\Rule\Io\ImporterPool;

class ImporterPoolTest extends TestCase
{
    public function testGetReturnsConfiguredImporter(): void
    {
        $csv = $this->createMock(ImporterInterface::class);
        $pool = new ImporterPool(['csv' => $csv]);

        self::assertSame($csv, $pool->get('csv'));
    }

    public function testGetThrowsOnUnknownFormat(): void
    {
        $pool = new ImporterPool([]);

        $this->expectException(LocalizedException::class);
        $pool->get('yaml');
    }

    /**
     * @dataProvider formatProvider
     */
    public function testDetectFormat(string $fileName, string $expected): void
    {
        $pool = new ImporterPool([]);

        self::assertSame($expected, $pool->detectFormat($fileName));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function formatProvider(): array
    {
        return [
            'csv extension' => ['shipping_rules.csv', 'csv'],
            'json extension' => ['shipping_rules.json', 'json'],
            'uppercase csv' => ['RULES.CSV', 'csv'],
            'unknown defaults to json' => ['rules.txt', 'json'],
            'no extension defaults to json' => ['rules', 'json'],
        ];
    }
}
