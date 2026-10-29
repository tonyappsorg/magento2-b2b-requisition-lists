<?php
declare(strict_types=1);

namespace Tonyapps\B2bRequisitionLists\Block\Account;

use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Html\Link\Current;
use Magento\Framework\View\Element\Template\Context;
use Tonyapps\B2b\Model\CompanyContext;
use Tonyapps\B2b\Model\Permissions;

class RequisitionListsLink extends Current
{
    public function __construct(Context $context, DefaultPathInterface $defaultPath, private readonly CompanyContext $companyContext, array $data = [])
    {
        parent::__construct($context, $defaultPath, $data);
    }

    public function toHtml(): string
    {
        return $this->companyContext->isAllowed(Permissions::REQUISITION_LISTS_VIEW) ? parent::toHtml() : '';
    }
}
