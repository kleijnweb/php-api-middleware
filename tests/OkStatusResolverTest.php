<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Middleware\Tests;

use KleijnWeb\PhpApi\Descriptions\Description\Operation;
use KleijnWeb\PhpApi\Middleware\Util\OkStatusResolver;
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
        $this->resolver = new OkStatusResolver();
    }

    public function testWillReturn200ForNullResultWhen204NotAvailable()
    {
        $statusCode = $this->resolver->resolve(
            null, $this->getMockBuilder(Operation::class)
            ->disableOriginalConstructor()
            ->getMock()
        );

        self::assertSame(200, $statusCode);
    }

    public function testWillReturn204ForNullResultWhenAvailable()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $operationMock */
        $operationMock = $operation = $this->getMockBuilder(Operation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $operationMock
            ->expects(self::once())
            ->method('getStatusCodes')
            ->willReturn([200, 204]);

        $statusCode = $this->resolver->resolve(null, $operation);

        self::assertSame(204, $statusCode);
    }
}
