<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\Rule\Io;

use Magento\Framework\Exception\LocalizedException;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Model\Rule;

/**
 * Imports rules from the CSV format produced by {@see CsvExporter}.
 */
readonly class CsvImporter
{
    public function __construct(
        private RuleArrayConverter $converter
    ) {
    }

    public function import(string $content): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $header = fgetcsv($handle, escape: '\\');
        if ($header === false || $header === null) {
            fclose($handle);
            throw new LocalizedException(__('The uploaded CSV file is empty.'));
        }

        $index = array_flip(array_map('strval', $header));
        $listFields = [
            RuleInterface::APPLIES_TO_METHODS,
            RuleInterface::CUSTOMER_GROUP_IDS,
            RuleInterface::STORE_IDS,
        ];

        $rules = [];
        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $data = [];
            foreach (CsvExporter::HEADER as $field) {
                $value = isset($index[$field]) ? ($row[$index[$field]] ?? '') : '';

                if ($field === RuleArrayConverter::CONDITIONS_KEY) {
                    $data[$field] = $this->decodeConditions((string)$value);
                } elseif (in_array($field, $listFields, true)) {
                    $data[$field] = Rule::normalizeList($value);
                } else {
                    $data[$field] = $value;
                }
            }

            $rules[] = $this->converter->fromArray($data);
        }

        fclose($handle);

        if ($rules === []) {
            throw new LocalizedException(__('No rules were found in the uploaded file.'));
        }

        return $rules;
    }

    /**
     * @param array<int, string|null> $row
     */
    private function isBlankRow(array $row): bool
    {
        return array_filter($row, fn($value): bool => $value !== null && trim((string)$value) !== '') === [];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeConditions(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
