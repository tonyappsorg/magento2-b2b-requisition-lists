<?php
declare(strict_types=1);

namespace Tonyapps\B2bRequisitionLists\Block\List;

use Magento\Framework\View\Element\Template;
use Tonyapps\B2b\Model\CompanyContext;
use Tonyapps\B2b\Model\Permissions;
use Tonyapps\B2bRequisitionLists\Model\RequisitionListRepository;

class Index extends Template
{
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
        $company = $this->companyContext->getCompany();
        return $company ? $this->listRepository->getForCompany((int) $company->getId()) : [];
    }

    public function canManage(): bool
    {
        return $this->companyContext->isAllowed(Permissions::REQUISITION_LISTS_MANAGE);
    }
}
