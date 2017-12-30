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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ParameterHydrator extends PhpApiMiddleware
{
    /**
     * @var ProcessorBuilder
     */
    private $processorBuilder;

    /**
     * ParameterHydrator constructor.
     * @param ProcessorBuilder $processorBuilder
     */
    public function __construct(ProcessorBuilder $processorBuilder)
    {
        $this->processorBuilder = $processorBuilder;
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
        $operation = $this->getOperation($request);

        foreach ($operation->getParameters() as $parameter) {
            $name    = $parameter->getName();
            $request = $request->withAttribute(
                $name,
                $this->processorBuilder
                    ->build($this->getOperation($request)->getParameter($name)->getSchema())
                    ->hydrate($request->getAttribute($name))
            );
        }

        return $delegate->process($request);
    }
}
