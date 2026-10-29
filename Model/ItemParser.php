<?php
declare(strict_types=1);

namespace Tonyapps\B2bRequisitionLists\Model;

class ItemParser
{
    public const MAX_LINES = 500;
    public const MAX_QTY = 10000;
    public const MAX_BYTES = 65536;

    /** @return array{rows: array<int, array{sku:string,qty:float}>, errors:string[]} */
    public function parse(string $input): array
    {
        if (strlen($input) > self::MAX_BYTES) {
            return ['rows' => [], 'errors' => [(string) __('List item text is too large.')]];
        }
        $rows = [];
        $errors = [];
        $lines = preg_split('/\r\n|\r|\n/', $input) ?: [];
        $lineCount = 0;
        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }
            $lineCount++;
            if ($lineCount > self::MAX_LINES) {
                $errors[] = (string) __('A list can contain no more than %1 item rows.', self::MAX_LINES);
                break;
            }
            $columns = str_getcsv($line, ',', '"', '\\');
            $sku = trim((string) ($columns[0] ?? ''));
            $qtyText = trim((string) ($columns[1] ?? ''));
            $qty = preg_match('/^\d+(?:\.\d{1,4})?$/D', $qtyText) === 1 ? (float) $qtyText : NAN;
            if (count($columns) !== 2 || $sku === '' || strlen($sku) > 64 || !is_finite($qty) || $qty <= 0 || $qty > self::MAX_QTY) {
                $errors[] = (string) __('Line %1: enter a SKU and a quantity between 0 and %2.', $index + 1, self::MAX_QTY);
                continue;
            }
            $key = strtolower($sku);
            $rows[$key] ??= ['sku' => $sku, 'qty' => 0.0];
            $rows[$key]['qty'] += $qty;
            if ($rows[$key]['qty'] > self::MAX_QTY) {
                $errors[] = (string) __('Line %1: the combined quantity for SKU "%2" exceeds %3.', $index + 1, $sku, self::MAX_QTY);
                unset($rows[$key]);
            }
        }
        return ['rows' => array_values($rows), 'errors' => $errors];
    }
}
