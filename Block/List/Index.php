<?php
declare(strict_types=1);

namespace Tonyapps\B2bRequisitionLists\Block\List;

use Magento\Framework\View\Element\Template;
use Tonyapps\B2b\Model\CompanyContext;
use Tonyapps\B2b\Model\Permissions;
use Tonyapps\B2bRequisitionLists\Model\RequisitionListRepository;

class Index extends Template
{
    private const PAGE_SIZE = 20;

    private ?array $lists = null;

    public function __construct(
        Template\Context $context,
        private readonly CompanyContext $companyContext,
        private readonly RequisitionListRepository $listRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getLists(): array
    {
        return array_slice($this->loadPage(), 0, self::PAGE_SIZE);
    }

    public function getPreviousPageUrl(): ?string
    {
        return $this->getPage() > 1 ? $this->getUrl('*/*/*', ['_query' => ['p' => $this->getPage() - 1]]) : null;
    }

    public function getNextPageUrl(): ?string
    {
        return count($this->loadPage()) > self::PAGE_SIZE
            ? $this->getUrl('*/*/*', ['_query' => ['p' => $this->getPage() + 1]])
            : null;
    }

    /** @return array<int, array<string, mixed>> one list more than a page, to know whether another page exists */
    private function loadPage(): array
    {
        if ($this->lists === null) {
            $company = $this->companyContext->getCompany();
            $this->lists = $company
                ? $this->listRepository->getForCompany((int) $company->getId(), self::PAGE_SIZE + 1, ($this->getPage() - 1) * self::PAGE_SIZE)
                : [];
        }
        return $this->lists;
    }

    private function getPage(): int
    {
        return max(1, (int) $this->getRequest()->getParam('p', 1));
    }

    public function canManage(): bool
    {
        return $this->companyContext->isAllowed(Permissions::REQUISITION_LISTS_MANAGE);
    }
}
