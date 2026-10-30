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
use Tonyapps\B2bQuickOrder\Model\QuickOrderService;
use Tonyapps\B2bRequisitionLists\Model\RequisitionListRepository;

class AddToCart extends AbstractCompanyAction implements HttpPostActionInterface
{
    protected string $requiredPermission = Permissions::REQUISITION_LISTS_VIEW;

    public function __construct(
        RequestInterface $request,
        ResultFactory $resultFactory,
        CompanyContext $companyContext,
        Session $customerSession,
        ManagerInterface $messageManager,
        UrlInterface $url,
        private readonly RequisitionListRepository $listRepository,
        private readonly QuickOrderService $quickOrderService
    ) {
        parent::__construct($request, $resultFactory, $companyContext, $customerSession, $messageManager, $url);
    }

    protected function executeForCompany(CompanyInterface $company): ResultInterface
    {
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        try {
            $list = $this->listRepository->getById((int) $company->getId(), (int) $this->request->getParam('list_id'));
            $rows = [];
            foreach (array_values($list['items']) as $index => $item) {
                // Number rows as they appear in the list so errors do not all read "Line ?".
                $rows[] = $item + ['line' => $index + 1];
            }
            $result = $this->quickOrderService->addRows($rows);
            if ($result['added'] > 0) {
                $this->messageManager->addSuccessMessage(__('Added %1 item row(s) from "%2" to your cart.', $result['added'], $list['name']));
            }
            foreach (array_slice($result['errors'], 0, 10) as $error) {
                $this->messageManager->addErrorMessage($error);
            }
            if (!$list['items']) {
                $this->messageManager->addNoticeMessage(__('This requisition list is empty.'));
            }
        } catch (\Throwable) {
            $this->messageManager->addErrorMessage(__('The requisition list could not be added to the cart.'));
        }
        return $redirect->setPath('checkout/cart');
    }
}
