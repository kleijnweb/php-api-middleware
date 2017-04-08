<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use KleijnWeb\PhpApi\Middleware\Body\BodySerializer;
use KleijnWeb\PhpApi\Middleware\Util\PhpApiMiddleware;
use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResultSerializer extends PhpApiMiddleware
{
    /**
     * @var BodySerializer
     */
    private $bodySerializer;

    /**
     * @var OkStatusResolver
     */
    private $okStatusResolver;

    /**
     * @param BodySerializer   $bodySerializer
     * @param OkStatusResolver $okStatusResolver
     */
    public function __construct(BodySerializer $bodySerializer, OkStatusResolver $okStatusResolver = null)
    {
        $this->bodySerializer   = $bodySerializer;
        $this->okStatusResolver = $okStatusResolver ?: new OkStatusResolver();
    }

    /**
     * Process a server request and return a response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $result    = $request->getAttribute(CommandDispatcher::ATTRIBUTE);
        $operation = $this->getOperation($request);

        return Factory::createResponse($this->okStatusResolver->resolve($result, $operation))
            ->withBody($this->createStringStream($this->bodySerializer->serialize($result)))
            ->withHeader('Content-Type', $this->bodySerializer->getContentType());
    }
}