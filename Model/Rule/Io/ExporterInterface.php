<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\Rule\Io;

use Tonyapps\ShippingRules\Model\Rule;

/**
 * Serializes a set of rules to a downloadable string.
 */
interface ExporterInterface
{
    /**
     * @param Rule[] $rules
     */
    public function export(array $rules): string;

    public function getContentType(): string;

    public function getFileName(): string;
}
