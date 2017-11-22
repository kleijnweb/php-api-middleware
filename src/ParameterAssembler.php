<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use KleijnWeb\PhpApi\Descriptions\Request\RequestParameterAssembler;
use KleijnWeb\PhpApi\Middleware\Util\PhpApiMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ParameterAssembler extends PhpApiMiddleware
{
    /**
     * @var RequestParameterAssembler
     */
    private $parameterAssembler;

    /**
     * @param RequestParameterAssembler $parameterAssembler
     */
    public function __construct(RequestParameterAssembler $parameterAssembler)
    {
        $this->parameterAssembler = $parameterAssembler;
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

        foreach ($this->parameterAssembler->getRequestParameters($request, $operation) as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        return $delegate->process($request);
    }
}
