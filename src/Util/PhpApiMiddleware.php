<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Middleware\Util;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use KleijnWeb\PhpApi\Descriptions\Description\Description;
use KleijnWeb\PhpApi\Descriptions\Description\Operation;
use KleijnWeb\PhpApi\Descriptions\Description\Path;
use Middlewares\Utils\Factory\StreamFactory;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Stream;

abstract class PhpApiMiddleware implements MiddlewareInterface
{
    /**
     * @var StreamFactory
     */
    private $streamFactory;

    /**
     * @param string $string
     * @return Stream
     */
    protected function createStringStream(string $string): Stream
    {
        $stream = $this->getStreamFactory()->createStream($string);
        $stream->rewind();

        return $stream;
    }

    /**
     * @param ServerRequestInterface $request
     * @return Description
     */
    protected function getDescription(ServerRequestInterface $request): Description
    {
        return $this->getMeta($request)->getDescription();
    }

    /**
     * @param ServerRequestInterface $request
     * @return Path
     */
    protected function getPath(ServerRequestInterface $request): Path
    {
        return $this->getMeta($request)->getPath();
    }

    /**
     * @param ServerRequestInterface $request
     * @return Operation
     */
    protected function getOperation(ServerRequestInterface $request): Operation
    {
        return $this->getMeta($request)->getOperation();
    }

    /**
     * @return StreamFactory
     */
    protected function getStreamFactory(): StreamFactory
    {
        if (!$this->streamFactory) {
            $this->streamFactory = new StreamFactory();
        }

        return $this->streamFactory;
    }

    /**
     * @param ServerRequestInterface $request
     * @return Meta
     */
    private function getMeta(ServerRequestInterface $request): Meta
    {
        return Meta::getFromRequest($request);
    }
}