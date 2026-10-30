<?php
declare(strict_types=1);

namespace Tonyapps\B2bRequisitionLists\Test\Unit\Block\List;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\TestCase;
use Tonyapps\B2b\Api\Data\CompanyInterface;
use Tonyapps\B2b\Model\CompanyContext;
use Tonyapps\B2bRequisitionLists\Block\List\Index;
use Tonyapps\B2bRequisitionLists\Model\RequisitionListRepository;

class IndexTest extends TestCase
{
    public function testLoadsOnePageOfListsAtATime(): void
    {
        $lists = array_map(static fn(int $id): array => ['entity_id' => $id, 'items' => []], range(1, 21));
        $repository = $this->createMock(RequisitionListRepository::class);
        $repository->expects(self::once())->method('getForCompany')->with(5, 21, 20)->willReturn($lists);

        $block = $this->block($repository, 2);

        self::assertCount(20, $block->getLists());
        self::assertSame('url:p=1', $block->getPreviousPageUrl());
        self::assertSame('url:p=3', $block->getNextPageUrl());
    }

    public function testSinglePageHasNoPagerLinks(): void
    {
        $repository = $this->createMock(RequisitionListRepository::class);
        $repository->method('getForCompany')->with(5, 21, 0)->willReturn([['entity_id' => 1, 'items' => []]]);

        $block = $this->block($repository, 1);

        self::assertCount(1, $block->getLists());
        self::assertNull($block->getPreviousPageUrl());
        self::assertNull($block->getNextPageUrl());
    }

    private function block(RequisitionListRepository $repository, int $page): Index
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->with('p', 1)->willReturn((string) $page);
        $url = $this->createMock(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn(string $route, array $params): string => 'url:p=' . $params['_query']['p']
        );
        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getUrlBuilder')->willReturn($url);
        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn(5);
        $companyContext = $this->createMock(CompanyContext::class);
        $companyContext->method('getCompany')->willReturn($company);

        return new Index($context, $companyContext, $repository);
    }
}
