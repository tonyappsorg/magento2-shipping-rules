<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\Rule\Io;

use Magento\Framework\Exception\LocalizedException;

/**
 * Resolves an exporter by format code (json|csv), configured via di.xml.
 */
class ExporterPool
{
    /**
     * @param array<string, ExporterInterface> $exporters
     */
    public function __construct(
        private readonly array $exporters = []
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function get(string $format): ExporterInterface
    {
        $format = strtolower($format);
        if (!isset($this->exporters[$format]) || !$this->exporters[$format] instanceof ExporterInterface) {
            throw new LocalizedException(__('Unsupported export format "%1".', $format));
        }

        return $this->exporters[$format];
    }
}
