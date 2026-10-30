<?php
declare(strict_types=1);

namespace Tonyapps\B2bRequisitionLists\Test\Unit\View;

use PHPUnit\Framework\TestCase;

class LayoutLocationTest extends TestCase
{
    /** Magento only reads storefront layouts from view/frontend/layout. */
    public function testStorefrontLayoutsAreWhereMagentoLoadsThem(): void
    {
        $root = __DIR__ . '/../../..';
        foreach (['b2brequisitionlists_list_index.xml', 'customer_account.xml'] as $file) {
            self::assertFileExists($root . '/view/frontend/layout/' . $file);
        }
        self::assertDirectoryDoesNotExist($root . '/etc/frontend/layout');
    }

    /** Company lists must never come from the full page cache (it also emptied the session, hiding manage forms). */
    public function testListPageIsUncachedAndUsesTheAccountLayout(): void
    {
        $layout = simplexml_load_file(__DIR__ . '/../../../view/frontend/layout/b2brequisitionlists_list_index.xml');

        self::assertSame('customer_account', (string) $layout->update['handle']);
        self::assertSame('false', (string) $layout->xpath('//block[@name="tonyapps.b2b.requisitionlists"]')[0]['cacheable']);
    }
}
