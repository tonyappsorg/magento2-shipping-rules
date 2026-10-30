<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\Rule\Io;

use Magento\Framework\Exception\LocalizedException;

/**
 * Imports rules from the JSON format produced by {@see JsonExporter}.
 */
class JsonImporter
{
    public function __construct(
        private readonly RuleArrayConverter $converter
    ) {
    }

    public function import(string $content): array
    {
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new LocalizedException(__('The uploaded file is not a valid JSON rule export.'));
        }

        $rules = [];
        foreach ($decoded as $item) {
            if (is_array($item)) {
                $rules[] = $this->converter->fromArray($item);
            }
        }

        if ($rules === []) {
            throw new LocalizedException(__('No rules were found in the uploaded file.'));
        }

        return $rules;
    }
}
