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
}
