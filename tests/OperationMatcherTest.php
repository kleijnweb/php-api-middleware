<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Middleware\Tests;

use Equip\Dispatch\Delegate;
use KleijnWeb\PhpApi\Descriptions\Description\Description;
use KleijnWeb\PhpApi\Descriptions\Description\Operation;
use KleijnWeb\PhpApi\Descriptions\Description\Parameter;
use KleijnWeb\PhpApi\Descriptions\Description\Path;
use KleijnWeb\PhpApi\Descriptions\Description\Repository;
use KleijnWeb\PhpApi\Descriptions\Description\Respository\RepositoryIterator;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Middleware\OperationMatcher;
use KleijnWeb\PhpApi\Middleware\Util\Meta;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class OperationMatcherTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $description;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $path;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $operation;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var OperationMatcher
     */
    private $matcher;

    /**
     * @var callable
     */
    private $noopFn;


    protected function setUp()
    {
        $this->description = $this->getMockBuilder(Description::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->path = $this->getMockBuilder(Path::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->operation = $this->getMockBuilder(Operation::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Repository $repository */
        $this->repository = $repository = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository
            ->expects($this->any())
            ->method('getUris')
            ->willReturn(['/path/to/document.yml']);

        $this->repository
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn(new RepositoryIterator($this->repository));

        $this->repository
            ->expects($this->any())
            ->method('get')
            ->willReturn($this->description);

        $this->matcher = new OperationMatcher($repository);

        $this->noopFn = function () {
        };
    }

    /**
     * @test
     */
    public function willReturn404WhenNoMatchingPaths()
    {
        $response = $this->matcher->process(
            Factory::createServerRequest([], 'GET', '/foo'), new Delegate([], $this->noopFn)
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function willReturn405WhenPathMatchesButWrongMethod()
    {
        $this->mockOperation('/foo', 'GET');

        $response = $this->matcher->process(
            Factory::createServerRequest([], 'POST', '/foo'), new Delegate([], $this->noopFn)
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(405, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function willCallNextWhenMatch()
    {
        $this->mockOperation('/foo', 'GET');

        $matched = false;
        $next    = function () use (&$matched) {
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', '/foo'), new Delegate([], $next));

        $this->assertTrue($matched);
    }

    /**
     * @test
     */
    public function willSetMetaWhenMatch()
    {
        $this->mockOperation('/foo', 'GET');

        $matched = false;
        $next    = function (ServerRequestInterface $request) use (&$matched) {
            $this->assertInstanceOf(Meta::class, $request->getAttribute(Meta::NAME));
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', '/foo'), new Delegate([], $next));

        $this->assertTrue($matched);
    }

    /**
     * @test
     */
    public function canMatchPathWithParam()
    {
        $this->mockOperation('/foo/{bar}', 'GET');

        $parameter = $this->getMockBuilder(Parameter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schema = $this->getMockBuilder(ScalarSchema::class)
            ->disableOriginalConstructor()
            ->getMock();

        $parameter->expects($this->once())->method('getName')->willReturn('bar');
        $parameter->expects($this->once())->method('getIn')->willReturn(Parameter::IN_PATH);
        $parameter->expects($this->once())->method('getSchema')->willReturn($schema);

        $this->operation->expects($this->once())->method('getParameters')->willReturn([$parameter]);

        $matched = false;
        $valueOfBar = "value-of-bar";
        $next    = function (ServerRequestInterface $request) use (&$matched, $valueOfBar) {
            $this->assertSame($valueOfBar, $request->getAttribute('bar'));
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        $this->assertTrue($matched);
    }

    /**
     * @test
     */
    public function canMatchPathWithIntParam()
    {
        $this->mockOperation('/foo/{bar}', 'GET');

        $parameter = $this->getMockBuilder(Parameter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schema = $this->getMockBuilder(ScalarSchema::class)
            ->disableOriginalConstructor()
            ->getMock();

        $parameter->expects($this->exactly(2))->method('getName')->willReturn('bar');
        $parameter->expects($this->exactly(2))->method('getIn')->willReturn(Parameter::IN_PATH);
        $parameter->expects($this->exactly(2))->method('getSchema')->willReturn($schema);
        $schema->expects($this->exactly(2))->method('getType')->willReturn(Schema::TYPE_INT);
        $this->operation->expects($this->exactly(2))->method('getParameters')->willReturn([$parameter]);

        $matched = false;
        $valueOfBar = "1";
        $next    = function (ServerRequestInterface $request) use (&$matched, $valueOfBar) {
            $this->assertSame($valueOfBar, $request->getAttribute('bar'));
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        $this->assertTrue($matched);

        $matched = false;
        $valueOfBar = "string-value";
        $next    = function () use (&$matched, $valueOfBar) {
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        $this->assertFalse($matched);
    }

    /**
     * @test
     */
    public function canMatchPathWithPatternedParam()
    {
        $this->mockOperation('/foo/{bar}', 'GET');

        $parameter = $this->getMockBuilder(Parameter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schema = $this->getMockBuilder(ScalarSchema::class)
            ->disableOriginalConstructor()
            ->getMock();

        $parameter->expects($this->exactly(2))->method('getName')->willReturn('bar');
        $parameter->expects($this->exactly(2))->method('getIn')->willReturn(Parameter::IN_PATH);
        $parameter->expects($this->exactly(2))->method('getSchema')->willReturn($schema);
        $schema->expects($this->exactly(2))->method('getType')->willReturn(Schema::TYPE_STRING);
        $schema->expects($this->exactly(2))->method('getPattern')->willReturn('[a-z]');
        $this->operation->expects($this->exactly(2))->method('getParameters')->willReturn([$parameter]);

        $matched = false;
        $valueOfBar = "a";
        $next    = function (ServerRequestInterface $request) use (&$matched, $valueOfBar) {
            $this->assertSame($valueOfBar, $request->getAttribute('bar'));
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        $this->assertTrue($matched);

        $matched = false;
        $valueOfBar = "abcd";
        $next    = function () use (&$matched, $valueOfBar) {
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        $this->assertFalse($matched);
    }

    /**
     * @test
     */
    public function canMatchPathWithEnumParam()
    {
        $this->mockOperation('/foo/{bar}', 'GET');

        $parameter = $this->getMockBuilder(Parameter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schema = $this->getMockBuilder(ScalarSchema::class)
            ->disableOriginalConstructor()
            ->getMock();

        $parameter->expects($this->exactly(2))->method('getName')->willReturn('bar');
        $parameter->expects($this->exactly(2))->method('getIn')->willReturn(Parameter::IN_PATH);
        $parameter->expects($this->exactly(2))->method('getSchema')->willReturn($schema);
        $schema->expects($this->exactly(2))->method('getType')->willReturn(Schema::TYPE_STRING);
        $schema->expects($this->exactly(2))->method('getEnum')->willReturn(['a', 'b']);
        $this->operation->expects($this->exactly(2))->method('getParameters')->willReturn([$parameter]);

        $matched = false;
        $valueOfBar = "a";
        $next    = function (ServerRequestInterface $request) use (&$matched, $valueOfBar) {
            $this->assertSame($valueOfBar, $request->getAttribute('bar'));
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        $this->assertTrue($matched);

        $matched = false;
        $valueOfBar = "c";
        $next    = function () use (&$matched, $valueOfBar) {
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        $this->assertFalse($matched);
    }

    /**
     * @param string $pathString
     * @param string $method
     */
    private function mockOperation(string $pathString, string $method)
    {
        $this->description->expects($this->any())->method('getPaths')->willReturn([$this->path]);
        $this->path->expects($this->any())->method('getPath')->willReturn($pathString);
        $this->path->expects($this->any())->method('getOperations')->willReturn([$this->operation]);
        $this->operation->expects($this->any())->method('getMethod')->willReturn(strtolower($method));
    }
}
