<?php
declare(strict_types=1);

namespace Tonyapps\B2bRequisitionLists\Test\Integration\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Tonyapps\B2bRequisitionLists\Model\RequisitionListRepository;

/**
 * @magentoDataFixture Magento/Customer/_files/customer.php
 * @magentoDbIsolation enabled
 */
class RequisitionListRepositoryTest extends TestCase
{
    public function testListItemsAreScopedToTheOwningCompany(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var ResourceConnection $resource */
        $resource = $objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $companyTable = $resource->getTableName('tonyapps_b2b_company');
        $nameA = 'Requisition Test A ' . uniqid();
        $nameB = 'Requisition Test B ' . uniqid();
        $connection->insertMultiple($companyTable, [
            ['name' => $nameA, 'status' => 1],
            ['name' => $nameB, 'status' => 1],
        ]);
        $companyA = (int) $connection->fetchOne($connection->select()->from($companyTable, ['entity_id'])->where('name = ?', $nameA));
        $companyB = (int) $connection->fetchOne($connection->select()->from($companyTable, ['entity_id'])->where('name = ?', $nameB));

        /** @var RequisitionListRepository $repository */
        $repository = $objectManager->create(RequisitionListRepository::class);
        $listId = $repository->save($companyA, 1, null, 'Quarterly stock', [['sku' => 'SKU-1', 'qty' => 2.0]]);

        self::assertCount(1, $repository->getForCompany($companyA));
        self::assertSame('SKU-1', $repository->getById($companyA, $listId)['items'][0]['sku']);
        self::assertSame([], $repository->getForCompany($companyB));
        $this->expectException(NoSuchEntityException::class);
        $repository->getById($companyB, $listId);
    }
}
