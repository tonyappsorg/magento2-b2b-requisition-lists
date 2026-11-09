<?php
declare(strict_types=1);

namespace Tonyapps\B2bRequisitionLists\Test\Unit\View;

use PHPUnit\Framework\TestCase;

class DeleteConfirmationTest extends TestCase
{
    /** Save Changes sits in the shared actions row but still submits that list's edit form. */
    public function testSaveChangesSubmitsTheListFormFromTheActionsRow(): void
    {
        $template = (string) file_get_contents(__DIR__ . '/../../../view/frontend/templates/list/index.phtml');

        self::assertStringContainsString('<form id="requisition-list-<?= (int) $list[\'entity_id\'] ?>"', $template);
        self::assertStringContainsString('form="requisition-list-<?= (int) $list[\'entity_id\'] ?>"', $template);
        self::assertMatchesRegularExpression('/requisition-list-actions.*Add List to Cart.*Save Changes.*Delete List/s', $template);
    }

    /** Deleting a shared list asks first: core's data-post widget shows a confirm modal before posting. */
    public function testDeleteListAsksForConfirmation(): void
    {
        $template = (string) file_get_contents(__DIR__ . '/../../../view/frontend/templates/list/index.phtml');

        self::assertMatchesRegularExpression(
            "/data-post=.*'action' => \\\$block->getUrl\\('b2brequisitionlists\\/list\\/delete'\\).*'confirmation' => true.*'confirmationMessage'/s",
            $template
        );
        self::assertStringNotContainsString("<form action=\"<?= \$escaper->escapeUrl(\$block->getUrl('b2brequisitionlists/list/delete'))", $template);
    }
}
