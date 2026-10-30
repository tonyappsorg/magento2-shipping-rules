<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Model\Rule\Io;

/**
 * Exports rules as a pretty-printed JSON array (faithful to nested conditions).
 */
class JsonExporter
{
    public function __construct(
        private readonly RuleArrayConverter $converter
    ) {
    }

    public function export(array $rules): string
    {
        $payload = [];
        foreach ($rules as $rule) {
            $payload[] = $this->converter->toArray($rule);
        }

        return (string)json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    public function getContentType(): string
    {
        return 'application/json';
    }

    public function getFileName(): string
    {
        return 'shipping_rules.json';
    }
}
