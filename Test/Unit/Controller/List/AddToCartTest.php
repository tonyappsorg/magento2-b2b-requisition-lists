<?php
declare(strict_types=1);

namespace Tonyapps\B2bRequisitionLists\Test\Unit\Controller\List;

use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;
use Tonyapps\B2b\Api\Data\CompanyInterface;
use Tonyapps\B2b\Model\CompanyContext;
use Tonyapps\B2bQuickOrder\Model\QuickOrderService;
use Tonyapps\B2bRequisitionLists\Controller\List\AddToCart;
use Tonyapps\B2bRequisitionLists\Model\RequisitionListRepository;

class AddToCartTest extends TestCase
{
    public function testRowsAreNumberedSoErrorsNameTheListLine(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->with('list_id')->willReturn('9');
        $redirect = $this->createMock(Redirect::class);
        $redirect->method('setPath')->willReturnSelf();
        $resultFactory = $this->createMock(ResultFactory::class);
        $resultFactory->method('create')->willReturn($redirect);
        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn(5);
        $companyContext = $this->createMock(CompanyContext::class);
        $companyContext->method('isEnabled')->willReturn(true);
        $companyContext->method('getCustomerId')->willReturn(21);
        $companyContext->method('getCompany')->willReturn($company);
        $companyContext->method('isAllowed')->willReturn(true);
        $repository = $this->createMock(RequisitionListRepository::class);
        $repository->method('getById')->with(5, 9)->willReturn([
            'name' => 'Weekly',
            'items' => [['sku' => 'A', 'qty' => 1.0], ['sku' => 'B', 'qty' => 2.0]],
        ]);
        $service = $this->createMock(QuickOrderService::class);
        $service->expects(self::once())->method('addRows')->with([
            ['sku' => 'A', 'qty' => 1.0, 'line' => 1],
            ['sku' => 'B', 'qty' => 2.0, 'line' => 2],
        ])->willReturn(['added' => 1, 'errors' => ['Line 2: SKU "B" was not found.']]);
        $messages = $this->createMock(ManagerInterface::class);
        $messages->expects(self::once())->method('addErrorMessage')->with('Line 2: SKU "B" was not found.');

        $controller = new AddToCart(
            $request,
            $resultFactory,
            $companyContext,
            $this->createMock(Session::class),
            $messages,
            $this->createMock(UrlInterface::class),
            $repository,
            $service
        );

        self::assertSame($redirect, $controller->execute());
    }
}
