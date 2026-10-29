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
use Tonyapps\B2bRequisitionLists\Model\ItemParser;
use Tonyapps\B2bRequisitionLists\Model\RequisitionListRepository;

class Save extends AbstractCompanyAction implements HttpPostActionInterface
{
    protected string $requiredPermission = Permissions::REQUISITION_LISTS_MANAGE;

    public function __construct(
        RequestInterface $request,
        ResultFactory $resultFactory,
        CompanyContext $companyContext,
        Session $customerSession,
        ManagerInterface $messageManager,
        UrlInterface $url,
        private readonly ItemParser $itemParser,
        private readonly RequisitionListRepository $listRepository
    ) {
        parent::__construct($request, $resultFactory, $companyContext, $customerSession, $messageManager, $url);
    }

    protected function executeForCompany(CompanyInterface $company): ResultInterface
    {
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $name = trim((string) $this->request->getParam('name', ''));
        if ($name === '' || mb_strlen($name) > 100) {
            $this->messageManager->addErrorMessage(__('List name is required and must be no longer than 100 characters.'));
            return $redirect->setPath('b2brequisitionlists/list/index');
        }
        $parsed = $this->itemParser->parse((string) $this->request->getParam('items', ''));
        foreach (array_slice($parsed['errors'], 0, 10) as $error) {
            $this->messageManager->addErrorMessage($error);
        }
        if ($parsed['errors']) {
            return $redirect->setPath('b2brequisitionlists/list/index');
        }
        try {
            $this->listRepository->save(
                (int) $company->getId(),
                (int) $this->companyContext->getCustomerId(),
                (int) $this->request->getParam('list_id') ?: null,
                $name,
                $parsed['rows']
            );
            $this->messageManager->addSuccessMessage(__('The requisition list was saved.'));
        } catch (\Throwable) {
            $this->messageManager->addErrorMessage(__('The requisition list could not be saved. Check that the name is unique within your company.'));
        }
        return $redirect->setPath('b2brequisitionlists/list/index');
    }
}
