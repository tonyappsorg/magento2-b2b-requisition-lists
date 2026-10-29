<?php
declare(strict_types=1);

namespace Tonyapps\B2bRequisitionLists\Controller\List;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Tonyapps\B2b\Api\Data\CompanyInterface;
use Tonyapps\B2b\Controller\AbstractCompanyAction;
use Tonyapps\B2b\Model\CompanyContext;
use Tonyapps\B2b\Model\Permissions;
use Tonyapps\B2bRequisitionLists\Model\RequisitionListRepository;

class Delete extends AbstractCompanyAction implements HttpPostActionInterface
{
    protected string $requiredPermission = Permissions::REQUISITION_LISTS_MANAGE;

    public function __construct(
        RequestInterface $request,
        ResultFactory $resultFactory,
        CompanyContext $companyContext,
        Session $customerSession,
        ManagerInterface $messageManager,
        UrlInterface $url,
        private readonly RequisitionListRepository $listRepository
    ) {
        parent::__construct($request, $resultFactory, $companyContext, $customerSession, $messageManager, $url);
    }

    protected function executeForCompany(CompanyInterface $company): ResultInterface
    {
        $deleted = $this->listRepository->delete((int) $company->getId(), (int) $this->request->getParam('list_id'));
        $this->messageManager->addNoticeMessage($deleted ? __('The requisition list was deleted.') : __('The requisition list was not found.'));
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('b2brequisitionlists/list/index');
    }
}
