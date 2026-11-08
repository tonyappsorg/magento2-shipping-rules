<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\View;

use Magento\Framework\Config\Dom;
use Magento\Framework\Config\Dom\UrnResolver;
use PHPUnit\Framework\TestCase;

class UiComponentSchemaTest extends TestCase
{
    public function testUiComponentsMatchSchema(): void
    {
        $xsd = (new UrnResolver())->getRealPath('urn:magento:module:Magento_Ui:etc/ui_configuration.xsd');
        $files = glob(__DIR__ . '/../../../view/*/ui_component/*.xml');
        $this->assertNotEmpty($files);
        foreach ($files as $file) {
            $dom = new \DOMDocument();
            $dom->load($file);
            $this->assertSame([], Dom::validateDomDocument($dom, $xsd), basename($file));
        }
    }
}
