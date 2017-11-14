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
            ->expects(self::any())
            ->method('getUris')
            ->willReturn(['/path/to/document.yml']);

        $this->repository
            ->expects(self::any())
            ->method('getIterator')
            ->willReturn(new RepositoryIterator($this->repository));

        $this->repository
            ->expects(self::any())
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

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(404, $response->getStatusCode());
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

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(405, $response->getStatusCode());
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

        self::assertTrue($matched);
    }

    /**
     * @test
     */
    public function willSetMetaWhenMatch()
    {
        $this->mockOperation('/foo', 'GET');

        $matched = false;
        $next    = function (ServerRequestInterface $request) use (&$matched) {
            self::assertInstanceOf(Meta::class, $request->getAttribute(Meta::NAME));
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', '/foo'), new Delegate([], $next));

        self::assertTrue($matched);
    }

    /**
     * @test
     */
    public function canMatchPathWithParam()
    {
        $this->mockOperation('/foo/{bar}', 'GET');
        $this->expectParameterType(Schema::TYPE_STRING);

        $matched    = false;
        $valueOfBar = "value-of-bar";
        $next       = function (ServerRequestInterface $request) use (&$matched, $valueOfBar) {
            self::assertSame($valueOfBar, $request->getAttribute('bar'));
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        self::assertTrue($matched);
    }

    /**
     * @test
     */
    public function canMatchPathWithIntParam()
    {
        $this->mockOperation('/foo/{bar}', 'GET');
        $this->expectParameterType(Schema::TYPE_NUMBER, 2);

        $matched    = false;
        $valueOfBar = "1";
        $next       = function (ServerRequestInterface $request) use (&$matched, $valueOfBar) {
            self::assertSame($valueOfBar, $request->getAttribute('bar'));
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        self::assertTrue($matched);

        $matched    = false;
        $valueOfBar = "string-value";
        $next       = function () use (&$matched, $valueOfBar) {
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        self::assertFalse($matched);
    }

    /**
     * @test
     */
    public function canMatchPathWithNumberParam()
    {
        $this->mockOperation('/foo/{bar}', 'GET');
        $this->expectParameterType(Schema::TYPE_NUMBER, 3);

        $matched    = false;
        $valueOfBar = "1.5";
        $next       = function (ServerRequestInterface $request) use (&$matched, $valueOfBar) {
            self::assertSame($valueOfBar, $request->getAttribute('bar'));
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        self::assertTrue($matched);

        $matched    = false;
        $valueOfBar = "5";
        $next       = function (ServerRequestInterface $request) use (&$matched, $valueOfBar) {
            self::assertSame($valueOfBar, $request->getAttribute('bar'));
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        self::assertTrue($matched);

        $matched    = false;
        $valueOfBar = "string-value";
        $next       = function () use (&$matched, $valueOfBar) {
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        self::assertFalse($matched);
    }

    /**
     * @test
     */
    public function canMatchPathWithPatternedParam()
    {
        $this->mockOperation('/foo/{bar}', 'GET');
        $schema = $this->expectParameterType(Schema::TYPE_STRING, 2);

        $schema->expects($this->exactly(2))->method('getPattern')->willReturn('[a-z]');

        $matched    = false;
        $valueOfBar = "a";
        $next       = function (ServerRequestInterface $request) use (&$matched, $valueOfBar) {
            self::assertSame($valueOfBar, $request->getAttribute('bar'));
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        self::assertTrue($matched);

        $matched    = false;
        $valueOfBar = "abcd";
        $next       = function () use (&$matched, $valueOfBar) {
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        self::assertFalse($matched);
    }

    /**
     * @test
     */
    public function canMatchPathWithEnumParam()
    {
        $this->mockOperation('/foo/{bar}', 'GET');
        $schema = $this->expectParameterType(Schema::TYPE_STRING, 2);
        $schema->expects(self::exactly(2))->method('getEnum')->willReturn(['a', 'b']);

        $matched    = false;
        $valueOfBar = "a";
        $next       = function (ServerRequestInterface $request) use (&$matched, $valueOfBar) {
            self::assertSame($valueOfBar, $request->getAttribute('bar'));
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        self::assertTrue($matched);

        $matched    = false;
        $valueOfBar = "c";
        $next       = function () use (&$matched, $valueOfBar) {
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/$valueOfBar"), new Delegate([], $next));

        self::assertFalse($matched);
    }

    /**
     * @test
     */
    public function willMatchTypeNull()
    {
        $this->mockOperation('/foo/{bar}', 'GET');
        $this->expectParameterType(Schema::TYPE_NULL);

        $matched = false;
        $next    = function () use (&$matched) {
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/null"), new Delegate([], $next));

        self::assertTrue($matched);
    }

    /**
     * @test
     */
    public function willMatchTypeAny()
    {
        $this->mockOperation('/foo/{bar}', 'GET');
        $this->expectParameterType(Schema::TYPE_ANY);

        $matched = false;
        $next    = function () use (&$matched) {
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/anything"), new Delegate([], $next));

        self::assertTrue($matched);
    }

    /**
     * @test
     */
    public function willMatchTypeObject()
    {
        $this->mockOperation('/foo/{bar}', 'GET');
        $this->expectParameterType(Schema::TYPE_OBJECT);

        $matched = false;
        $next    = function () use (&$matched) {
            $matched = true;
        };

        $this->matcher->process(Factory::createServerRequest([], 'GET', "/foo/anything"), new Delegate([], $next));

        self::assertTrue($matched);
    }

    private function expectParameterType(string $type, int $count = 1): \PHPUnit_Framework_MockObject_MockObject
    {
        $parameter = $this->getMockBuilder(Parameter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schema = $this->getMockBuilder(ScalarSchema::class)
            ->disableOriginalConstructor()
            ->getMock();

        $parameter->expects(self::exactly($count))->method('getName')->willReturn('bar');
        $parameter->expects(self::exactly($count))->method('getIn')->willReturn(Parameter::IN_PATH);
        $parameter->expects(self::exactly($count))->method('getSchema')->willReturn($schema);
        $schema->expects(self::exactly($count))->method('getType')->willReturn($type);
        $this->operation->expects(self::exactly($count))->method('getParameters')->willReturn([$parameter]);

        return $schema;
    }

    /**
     * @param string $pathString
     * @param string $method
     */
    private function mockOperation(string $pathString, string $method)
    {
        $this->description->expects(self::any())->method('getPaths')->willReturn([$this->path]);
        $this->path->expects(self::any())->method('getPath')->willReturn($pathString);
        $this->path->expects(self::any())->method('getOperations')->willReturn([$this->operation]);
        $this->operation->expects(self::any())->method('getMethod')->willReturn(strtolower($method));
    }
}
