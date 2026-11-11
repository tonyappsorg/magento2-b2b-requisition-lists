<?php
declare(strict_types=1);

namespace Tonyapps\B2bRequisitionLists\Test\Integration\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Tonyapps\B2bRequisitionLists\Model\RequisitionListRepository;

/**
 * Runs against the real schema: company scoping, the unique constraints, the transaction and the cascades.
 *
 * @magentoDataFixture Magento/Customer/_files/customer.php
 * @magentoDbIsolation enabled
 */
class RequisitionListRepositoryTest extends TestCase
{
    private AdapterInterface $connection;
    private ResourceConnection $resource;
    private RequisitionListRepository $repository;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->resource = $objectManager->get(ResourceConnection::class);
        $this->connection = $this->resource->getConnection();
        $this->repository = $objectManager->create(RequisitionListRepository::class);
    }

    public function testListItemsAreScopedToTheOwningCompany(): void
    {
        $companyA = $this->createCompany('A');
        $companyB = $this->createCompany('B');
        $listId = $this->repository->save($companyA, 1, null, 'Quarterly stock', [['sku' => 'SKU-1', 'qty' => 2.0]]);

        self::assertCount(1, $this->repository->getForCompany($companyA));
        self::assertSame('SKU-1', $this->repository->getById($companyA, $listId)['items'][0]['sku']);
        self::assertSame([], $this->repository->getForCompany($companyB));
        $this->expectException(NoSuchEntityException::class);
        $this->repository->getById($companyB, $listId);
    }

    public function testSavingAnExistingListReplacesItsNameAndItems(): void
    {
        $company = $this->createCompany('Edit');
        $listId = $this->repository->save($company, 1, null, 'Office', [
            ['sku' => 'PAPER', 'qty' => 10.0],
            ['sku' => 'PENS', 'qty' => 5.0],
        ]);

        self::assertSame($listId, $this->repository->save($company, 1, $listId, 'Office supplies', [
            ['sku' => 'TONER', 'qty' => 1.5],
        ]));

        $list = $this->repository->getById($company, $listId);
        self::assertSame('Office supplies', $list['name']);
        self::assertSame([['sku' => 'TONER', 'qty' => 1.5]], $list['items']);
        self::assertSame(1, (int) $list['created_by_customer_id']);
    }

    public function testAnotherCompanyCannotOverwriteTheList(): void
    {
        $owner = $this->createCompany('Owner');
        $other = $this->createCompany('Other');
        $listId = $this->repository->save($owner, 1, null, 'Owned', [['sku' => 'SKU-1', 'qty' => 1.0]]);

        try {
            $this->repository->save($other, 1, $listId, 'Hijacked', []);
            self::fail('Saving another company\'s list must be refused.');
        } catch (NoSuchEntityException) {
        }

        $list = $this->repository->getById($owner, $listId);
        self::assertSame('Owned', $list['name']);
        self::assertCount(1, $list['items']);
    }

    public function testListNamesAreUniquePerCompanyOnly(): void
    {
        $companyA = $this->createCompany('NameA');
        $companyB = $this->createCompany('NameB');
        $this->repository->save($companyA, 1, null, 'Monthly', []);

        self::assertGreaterThan(0, $this->repository->save($companyB, 1, null, 'Monthly', []));
        $this->expectException(\Exception::class);
        $this->repository->save($companyA, 1, null, 'Monthly', []);
    }

    /**
     * DB isolation wraps the test in a transaction, which would turn the repository's rollback into a no-op.
     *
     * @magentoDbIsolation disabled
     */
    public function testAFailedSaveLeavesTheListAsItWas(): void
    {
        $company = $this->createCompany('Rollback');
        try {
            $this->assertFailedSaveRollsBack($company);
        } finally {
            $this->connection->delete($this->resource->getTableName('tonyapps_b2b_company'), ['entity_id = ?' => $company]);
        }
    }

    private function assertFailedSaveRollsBack(int $company): void
    {
        $listId = $this->repository->save($company, 1, null, 'Before', [['sku' => 'KEEP', 'qty' => 3.0]]);

        try {
            // The item table allows each SKU once per list; the second row fails after the old items were deleted.
            $this->repository->save($company, 1, $listId, 'After', [
                ['sku' => 'DUP', 'qty' => 1.0],
                ['sku' => 'DUP', 'qty' => 2.0],
            ]);
            self::fail('A duplicate SKU must fail the save.');
        } catch (\Exception) {
        }

        $list = $this->repository->getById($company, $listId);
        self::assertSame('Before', $list['name']);
        self::assertSame([['sku' => 'KEEP', 'qty' => 3.0]], $list['items']);
    }

    public function testDeleteIsScopedToTheCompanyAndRemovesTheItems(): void
    {
        $owner = $this->createCompany('DelOwner');
        $other = $this->createCompany('DelOther');
        $listId = $this->repository->save($owner, 1, null, 'To delete', [['sku' => 'SKU-1', 'qty' => 1.0]]);

        self::assertFalse($this->repository->delete($other, $listId));
        self::assertCount(1, $this->repository->getForCompany($owner));

        self::assertTrue($this->repository->delete($owner, $listId));
        self::assertSame([], $this->repository->getForCompany($owner));
        self::assertSame(0, $this->countItems($listId));
    }

    public function testDeletingTheCompanyRemovesItsLists(): void
    {
        $company = $this->createCompany('Cascade');
        $listId = $this->repository->save($company, 1, null, 'Gone with the company', [['sku' => 'SKU-1', 'qty' => 1.0]]);

        $this->connection->delete($this->resource->getTableName('tonyapps_b2b_company'), ['entity_id = ?' => $company]);

        self::assertSame([], $this->repository->getForCompany($company));
        self::assertSame(0, $this->countItems($listId));
    }

    public function testDeletingTheCreatorKeepsTheList(): void
    {
        $company = $this->createCompany('Creator');
        $listId = $this->repository->save($company, 1, null, 'Shared', []);

        $this->connection->delete($this->resource->getTableName('customer_entity'), ['entity_id = ?' => 1]);

        self::assertNull($this->repository->getById($company, $listId)['created_by_customer_id']);
    }

    public function testListsAreSortedByNameAndPaged(): void
    {
        $company = $this->createCompany('Paging');
        foreach (['Charlie', 'Alpha', 'Bravo'] as $name) {
            $this->repository->save($company, 1, null, $name, []);
        }

        self::assertSame(['Alpha', 'Bravo', 'Charlie'], array_column($this->repository->getForCompany($company), 'name'));
        self::assertSame(['Bravo'], array_column($this->repository->getForCompany($company, 1, 1), 'name'));
    }

    private function createCompany(string $label): int
    {
        $table = $this->resource->getTableName('tonyapps_b2b_company');
        $this->connection->insert($table, ['name' => 'Requisition Test ' . $label . ' ' . uniqid(), 'status' => 1]);
        return (int) $this->connection->lastInsertId($table);
    }

    private function countItems(int $listId): int
    {
        return (int) $this->connection->fetchOne(
            $this->connection->select()
                ->from($this->resource->getTableName('tonyapps_b2b_requisition_list_item'), ['COUNT(*)'])
                ->where('list_id = ?', $listId)
        );
    }
}
