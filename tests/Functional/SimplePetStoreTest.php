<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Middleware\Tests\Functional;

use Doctrine\Common\Cache\ArrayCache;
use Equip\Dispatch\MiddlewarePipe;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use KleijnWeb\PhpApi\Descriptions\Description\Repository;
use KleijnWeb\PhpApi\Middleware\DefaultPipe;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class SimplePetStoreTest extends TestCase
{
    /**
     * @var DefaultPipe
     */
    private $pipe;

    /**
     * @var callable
     */
    private $serverErrorHandler;

    protected function setUp()
    {
        $cache = new ArrayCache();

        $commands = [
            '/pets/{id}:get' => function (int $id) use ($cache) {
                return unserialize($cache->fetch($id));
            },
            '/pets:post'     => function (Pet $pet) use ($cache) {
                $count = $cache->fetch('count');
                $pet->setId($id = $count + 1);
                $cache->save($id, serialize($pet));
                $cache->save('count', $id);

                return $pet;
            },
        ];

        $repository = new Repository(null, $cache);
        $repository->register(__DIR__.'/petstore.yml');

        $this->pipe = new DefaultPipe(
            $repository,
            $commands,
            ['KleijnWeb\PhpApi\Middleware\Tests\Functional']
        );

        $this->serverErrorHandler = function () {
            return Factory::createResponse(418);
        };
    }

    /**
     * @test
     */
    public function willReturn404WhenNoMatchingPaths()
    {
        $this->assertSame(404, $this->dispatch(new ServerRequest())->getStatusCode());
    }

    /**
     * @test
     */
    public function tryingToPostPetWithoutBodyReturns400()
    {
        $response = $this->dispatch($this->createRequest('/pets', '', 'POST'));

        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function canCreatePet()
    {
        $response = $this->dispatch($this->createRequest('/pets', json_encode(['name' => 'doggo'])));

        $this->assertSame(201, $response->getStatusCode());
        $contents = $response->getBody()->getContents();
        $this->assertSame(['id' => 1, 'name' => 'doggo'], json_decode($contents, true));
    }

    /**
     * @test
     */
    public function idsWillAutoincrement()
    {
        $response = $this->dispatch($this->createRequest('/pets', json_encode(['name' => 'doggo'])));

        $this->assertSame(201, $response->getStatusCode());
        $contents = $response->getBody()->getContents();
        $this->assertSame(['id' => 1, 'name' => 'doggo'], json_decode($contents, true));
        $response = $this->dispatch($this->createRequest('/pets', json_encode(['name' => 'doggo'])));

        $this->assertSame(201, $response->getStatusCode());
        $contents = $response->getBody()->getContents();
        $this->assertSame(['id' => 2, 'name' => 'doggo'], json_decode($contents, true));
    }

    /**
     * @test
     */
    public function canFetchPetById()
    {
        $this->canCreatePet();

        $response = $this->dispatch($this->createRequest('/pets/1'));

        $this->assertSame(200, $response->getStatusCode());
        $contents = $response->getBody()->getContents();
        $this->assertSame(['id' => 1, 'name' => 'doggo'], json_decode($contents, true));
    }

    /**
     * @test
     */
    public function willNotAttachResultSerializedWhenNotResponsing()
    {
        $this->pipe->setResponding(false);

        $response = $this->dispatch($this->createRequest('/pets', json_encode(['name' => 'doggo'])));

        $this->assertSame(418, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function canMiddlewareAppendToPipe()
    {
        $this->pipe->append(new class implements MiddlewareInterface
        {
            public function process(ServerRequestInterface $request, DelegateInterface $delegate)
            {
                return Factory::createResponse(302);
            }
        });

        $response = $this->dispatch($this->createRequest('/pets', json_encode(['name' => 'doggo'])));

        $this->assertSame(302, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function canAddPipeToPipe()
    {
        $this->pipe->setResponding(false);

        $middleware = [
            new class implements MiddlewareInterface
            {
                public function process(ServerRequestInterface $request, DelegateInterface $delegate)
                {
                    return $delegate->process($request->withMethod('GET'));
                }
            },
            $this->pipe,
            new class implements MiddlewareInterface
            {
                public function process(ServerRequestInterface $request, DelegateInterface $delegate)
                {
                    return Factory::createResponse(302);
                }
            },
        ];

        $pipe = new MiddlewarePipe($middleware);

        $response = $this->dispatch($this->createRequest('/pets', json_encode(['name' => 'doggo'])), $pipe);

        $this->assertSame(405, $response->getStatusCode());
    }

    private function dispatch(ServerRequestInterface $request, MiddlewarePipe $pipe = null): ResponseInterface
    {
        $pipe = $pipe ?: $this->pipe;

        return $pipe->dispatch($request, $this->serverErrorHandler);
    }

    private function createRequest(string $url, string $contents = '', $method = null): ServerRequestInterface
    {
        if (!$method) {
            $method = '' !== $contents ? 'POST' : 'GET';
        }

        $factory = new Factory\StreamFactory();
        $body    = $factory->createStream($contents);
        $body->rewind();

        return new ServerRequest([], [], $url, $method, $body);
    }
}
