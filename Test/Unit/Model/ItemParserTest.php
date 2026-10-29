<?php
declare(strict_types=1);

namespace Tonyapps\B2bRequisitionLists\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Tonyapps\B2bRequisitionLists\Model\ItemParser;

class ItemParserTest extends TestCase
{
    public function testParsesRowsAndMergesDuplicateSkusWithoutCaseSensitivity(): void
    {
        $result = (new ItemParser())->parse("SKU-1, 2\nsku-1, 1.5\nSKU-2, 4");

        self::assertSame([], $result['errors']);
        self::assertSame([
            ['sku' => 'SKU-1', 'qty' => 3.5],
            ['sku' => 'SKU-2', 'qty' => 4.0],
        ], $result['rows']);
    }

    public function testReportsInvalidRowsInsteadOfDroppingThemSilently(): void
    {
        $result = (new ItemParser())->parse("SKU-1,2\n,3\nSKU-2,0\nSKU-3,nan\nSKU-4,0.00001");

        self::assertCount(4, $result['errors']);
        self::assertSame([['sku' => 'SKU-1', 'qty' => 2.0]], $result['rows']);
    }

    public function testRejectsOversizedInputBeforeSplittingLines(): void
    {
        $result = (new ItemParser())->parse(str_repeat('x', ItemParser::MAX_BYTES + 1));

        self::assertSame([], $result['rows']);
        self::assertCount(1, $result['errors']);
    }
}
