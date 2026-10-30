<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Test\Unit\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule as RuleResource;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\RuleFactory;
use Tonyapps\ShippingRules\Model\RuleRepository;

class RuleRepositoryTest extends TestCase
{
    private RuleResource&MockObject $resource;
    private RuleFactory&MockObject $ruleFactory;
    private RuleRepository $repository;

    protected function setUp(): void
    {
        $this->resource = $this->createMock(RuleResource::class);
        $this->ruleFactory = $this->createMock(RuleFactory::class);

        $this->repository = new RuleRepository(
            $this->resource,
            $this->ruleFactory
        );
    }

    public function testSaveReturnsTheSavedRule(): void
    {
        $rule = $this->createMock(Rule::class);
        $this->resource->expects(self::once())->method('save')->with($rule);

        self::assertSame($rule, $this->repository->save($rule));
    }

    public function testSaveWrapsResourceExceptions(): void
    {
        $rule = $this->createMock(Rule::class);
        $this->resource->method('save')->willThrowException(new \RuntimeException('db is gone'));

        $this->expectException(CouldNotSaveException::class);

        $this->repository->save($rule);
    }

    public function testGetByIdReturnsLoadedRule(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getId')->willReturn(7);
        $this->ruleFactory->method('create')->willReturn($rule);
        $this->resource->expects(self::once())->method('load')->with($rule, 7);

        self::assertSame($rule, $this->repository->getById(7));
    }

    public function testGetByIdThrowsWhenRuleDoesNotExist(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getId')->willReturn(null);
        $this->ruleFactory->method('create')->willReturn($rule);

        $this->expectException(NoSuchEntityException::class);

        $this->repository->getById(999);
    }

    public function testDeleteReturnsTrueOnSuccess(): void
    {
        $rule = $this->createMock(Rule::class);
        $this->resource->expects(self::once())->method('delete')->with($rule);

        self::assertTrue($this->repository->delete($rule));
    }

    public function testDeleteWrapsResourceExceptions(): void
    {
        $rule = $this->createMock(Rule::class);
        $this->resource->method('delete')->willThrowException(new \RuntimeException('locked'));

        $this->expectException(CouldNotDeleteException::class);

        $this->repository->delete($rule);
    }

    public function testDeleteByIdLoadsThenDeletes(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getId')->willReturn(3);
        $this->ruleFactory->method('create')->willReturn($rule);
        $this->resource->expects(self::once())->method('delete')->with($rule);

        self::assertTrue($this->repository->deleteById(3));
    }

}
