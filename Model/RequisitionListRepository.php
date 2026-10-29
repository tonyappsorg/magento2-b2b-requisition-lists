<?php
declare(strict_types=1);

namespace Tonyapps\B2bRequisitionLists\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;

class RequisitionListRepository
{
    public function __construct(private readonly ResourceConnection $resource)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function getForCompany(int $companyId): array
    {
        if ($companyId < 1) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $listsTable = $this->resource->getTableName('tonyapps_b2b_requisition_list');
        $itemsTable = $this->resource->getTableName('tonyapps_b2b_requisition_list_item');
        $lists = $connection->fetchAll(
            $connection->select()->from($listsTable)->where('company_id = ?', $companyId)->order('name ASC')->order('entity_id ASC')
        );
        if (!$lists) {
            return [];
        }
        $items = $connection->fetchAll(
            $connection->select()->from($itemsTable)->where('list_id IN (?)', array_column($lists, 'entity_id'))->order('entity_id ASC')
        );
        $itemsByList = [];
        foreach ($items as $item) {
            $itemsByList[(int) $item['list_id']][] = ['sku' => (string) $item['sku'], 'qty' => (float) $item['qty']];
        }
        foreach ($lists as &$list) {
            $list['items'] = $itemsByList[(int) $list['entity_id']] ?? [];
        }
        unset($list);
        return $lists;
    }

    /** @return array<string, mixed> */
    public function getById(int $companyId, int $listId): array
    {
        if ($companyId < 1 || $listId < 1) {
            throw new NoSuchEntityException(__('The requisition list does not exist for this company.'));
        }
        $connection = $this->resource->getConnection();
        $list = $connection->fetchRow(
            $connection->select()->from($this->resource->getTableName('tonyapps_b2b_requisition_list'))
                ->where('entity_id = ?', $listId)->where('company_id = ?', $companyId)
        );
        if (!$list) {
            throw new NoSuchEntityException(__('The requisition list does not exist for this company.'));
        }
        $list['items'] = $connection->fetchAll(
            $connection->select()->from($this->resource->getTableName('tonyapps_b2b_requisition_list_item'), ['sku', 'qty'])
                ->where('list_id = ?', $listId)->order('entity_id ASC')
        );
        foreach ($list['items'] as &$item) {
            $item['sku'] = (string) $item['sku'];
            $item['qty'] = (float) $item['qty'];
        }
        unset($item);
        return $list;
    }

    /** @param array<int, array{sku:string,qty:float}> $items */
    public function save(int $companyId, int $customerId, ?int $listId, string $name, array $items): int
    {
        $connection = $this->resource->getConnection();
        $listsTable = $this->resource->getTableName('tonyapps_b2b_requisition_list');
        $itemsTable = $this->resource->getTableName('tonyapps_b2b_requisition_list_item');
        if ($listId) {
            $exists = $connection->fetchOne($connection->select()->from($listsTable, ['entity_id'])->where('entity_id = ?', $listId)->where('company_id = ?', $companyId));
            if (!$exists) {
                throw new NoSuchEntityException(__('The requisition list does not exist for this company.'));
            }
        }

        $connection->beginTransaction();
        try {
            if ($listId) {
                $connection->update($listsTable, ['name' => $name], ['entity_id = ?' => $listId, 'company_id = ?' => $companyId]);
            } else {
                $connection->insert($listsTable, ['company_id' => $companyId, 'created_by_customer_id' => $customerId, 'name' => $name]);
                $listId = (int) $connection->lastInsertId($listsTable);
            }
            $connection->delete($itemsTable, ['list_id = ?' => $listId]);
            if ($items) {
                $insertRows = [];
                foreach ($items as $item) {
                    $insertRows[] = ['list_id' => $listId, 'sku' => $item['sku'], 'qty' => $item['qty']];
                }
                $connection->insertMultiple($itemsTable, $insertRows);
            }
            $connection->commit();
            return (int) $listId;
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    public function delete(int $companyId, int $listId): bool
    {
        if ($companyId < 1 || $listId < 1) {
            return false;
        }
        return $this->resource->getConnection()->delete(
            $this->resource->getTableName('tonyapps_b2b_requisition_list'),
            ['entity_id = ?' => $listId, 'company_id = ?' => $companyId]
        ) > 0;
    }
}
