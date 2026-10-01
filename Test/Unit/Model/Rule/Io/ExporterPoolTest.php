<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Model\Rule\Io;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Tonyapps\ShippingRules\Model\Rule\Io\ExporterInterface;
use Tonyapps\ShippingRules\Model\Rule\Io\ExporterPool;

class ExporterPoolTest extends TestCase
{
    public function testGetReturnsConfiguredExporterCaseInsensitively(): void
    {
        $json = $this->createMock(ExporterInterface::class);
        $pool = new ExporterPool(['json' => $json]);

        self::assertSame($json, $pool->get('json'));
        self::assertSame($json, $pool->get('JSON'));
    }

    public function testGetThrowsOnUnknownFormat(): void
    {
        $pool = new ExporterPool([]);

        $this->expectException(LocalizedException::class);
        $pool->get('xml');
    }
}
