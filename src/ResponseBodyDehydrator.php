<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use KleijnWeb\PhpApi\Hydrator\Hydrator;
use KleijnWeb\PhpApi\Middleware\Body\BodySerializer;
use KleijnWeb\PhpApi\Middleware\Util\OkStatusResolver;
use KleijnWeb\PhpApi\Middleware\Util\PhpApiMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseBodyDehydrator extends PhpApiMiddleware
{
    /**
     * @var Hydrator
     */
    private $hydrator;

    /**
     * @var BodySerializer|OkStatusResolver
     */
    private $okStatusResolver;

    /**
     * @param Hydrator              $hydrator
     * @param OkStatusResolver|null $okStatusResolver
     */
    public function __construct(Hydrator $hydrator, OkStatusResolver $okStatusResolver = null)
    {
        $this->hydrator         = $hydrator;
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
        if (null !== ($data = $request->getAttribute(CommandDispatcher::ATTRIBUTE))) {
            $operation = $this->getOperation($request);
            $schema    = $operation
                ->getResponse($this->okStatusResolver->resolve($data, $operation))
                ->getSchema();

            $request = $request->withAttribute(
                CommandDispatcher::ATTRIBUTE,
                $this->hydrator->dehydrate($data, $schema)
            );
        }

        return $delegate->process($request);
    }
}
