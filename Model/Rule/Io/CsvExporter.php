<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\Rule\Io;

use Tonyapps\ShippingRules\Api\Data\RuleInterface;

/**
 * Exports rules as CSV.
 *
 * List columns (methods/groups/stores) are comma-joined inside a quoted cell; the nested condition
 * tree cannot be flattened losslessly, so it is embedded as a JSON string in the "conditions" cell.
 * escape: '' keeps it RFC 4180 (quotes doubled), so Excel and other tools read the JSON intact.
 */
class CsvExporter
{
    /**
     * CSV header row (order matters for the importer).
     */
    public const HEADER = [
        RuleInterface::NAME,
        RuleInterface::DESCRIPTION,
        RuleInterface::IS_ACTIVE,
        RuleInterface::SORT_ORDER,
        RuleInterface::ACTION,
        RuleInterface::APPLIES_TO_METHODS,
        RuleInterface::HIDE_OTHERS,
        RuleInterface::CUSTOMER_GROUP_IDS,
        RuleInterface::STORE_IDS,
        RuleInterface::FROM_DATE,
        RuleInterface::TO_DATE,
        RuleArrayConverter::CONDITIONS_KEY,
    ];

    public function __construct(
        private readonly RuleArrayConverter $converter
    ) {
    }

    public function export(array $rules): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::HEADER, escape: '');

        foreach ($rules as $rule) {
            $row = $this->converter->toArray($rule);
            fputcsv(
                $handle,
                [
                    (string)$row[RuleInterface::NAME],
                    (string)($row[RuleInterface::DESCRIPTION] ?? ''),
                    (string)$row[RuleInterface::IS_ACTIVE],
                    (string)$row[RuleInterface::SORT_ORDER],
                    (string)$row[RuleInterface::ACTION],
                    implode(',', $row[RuleInterface::APPLIES_TO_METHODS]),
                    (string)$row[RuleInterface::HIDE_OTHERS],
                    implode(',', $row[RuleInterface::CUSTOMER_GROUP_IDS]),
                    implode(',', $row[RuleInterface::STORE_IDS]),
                    (string)($row[RuleInterface::FROM_DATE] ?? ''),
                    (string)($row[RuleInterface::TO_DATE] ?? ''),
                    (string)json_encode($row[RuleArrayConverter::CONDITIONS_KEY], JSON_UNESCAPED_SLASHES),
                ],
                escape: ''
            );
        }

        rewind($handle);
        $csv = (string)stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public function getContentType(): string
    {
        return 'text/csv';
    }

    public function getFileName(): string
    {
        return 'shipping_rules.csv';
    }
}
