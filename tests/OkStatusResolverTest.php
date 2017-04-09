<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Middleware\Tests;

use KleijnWeb\PhpApi\Descriptions\Description\Operation;
use KleijnWeb\PhpApi\Middleware\OkStatusResolver;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class OkStatusResolverTest extends TestCase
{
    /**
     * @var OkStatusResolver
     */
    private $resolver;

    protected function setUp()
    {
        $this->resolver = new OkStatusResolver;
    }

    /**
     * @test
     */
    public function willReturn200ForNullResultWhen204NotAvailable()
    {
        $operationMock = $operation = $this->getMockBuilder(Operation::class)
            ->disableOriginalConstructor()
            ->getMock();


        $statusCode = $this->resolver->resolve(null, $operation);

        $this->assertSame(200, $statusCode);
    }

    /**
     * @test
     */
    public function willReturn204ForNullResultWhenAvailable()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $operationMock */
        $operationMock = $operation = $this->getMockBuilder(Operation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $operationMock
            ->expects($this->once())
            ->method('getStatusCodes')
            ->willReturn([200, 204]);

        $statusCode = $this->resolver->resolve(null, $operation);

        $this->assertSame(204, $statusCode);
    }
}
