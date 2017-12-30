<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use KleijnWeb\PhpApi\Descriptions\Hydrator\ProcessorBuilder;
use KleijnWeb\PhpApi\Middleware\Body\BodySerializer;
use KleijnWeb\PhpApi\Descriptions\Util\OkStatusResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseBodyDehydrator extends PhpApiMiddleware
{
    /**
     * @var ProcessorBuilder
     */
    private $processorBuilder;

    /**
     * @var BodySerializer|OkStatusResolver
     */
    private $okStatusResolver;

    /**
     * @param ProcessorBuilder              $processorBuilder
     * @param OkStatusResolver|null $okStatusResolver
     */
    public function __construct(ProcessorBuilder $processorBuilder, OkStatusResolver $okStatusResolver = null)
    {
        $this->processorBuilder = $processorBuilder;
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
                $this->processorBuilder->build($schema)->dehydrate($data)
            );
        }

        return $delegate->process($request);
    }
}
