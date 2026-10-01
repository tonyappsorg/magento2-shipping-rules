<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\Rule\Io;

use Magento\Framework\Exception\LocalizedException;

/**
 * Resolves an importer by format code (json|csv), configured via di.xml.
 */
class ImporterPool
{
    /**
     * @param array<string, ImporterInterface> $importers
     */
    public function __construct(
        private readonly array $importers = []
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function get(string $format): ImporterInterface
    {
        $format = strtolower($format);
        if (!isset($this->importers[$format]) || !$this->importers[$format] instanceof ImporterInterface) {
            throw new LocalizedException(__('Unsupported import format "%1".', $format));
        }

        return $this->importers[$format];
    }

    /**
     * Best-effort format detection from a file name extension.
     */
    public function detectFormat(string $fileName): string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return $extension === 'csv' ? 'csv' : 'json';
    }
}
