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
use KleijnWeb\PhpApi\Middleware\Util\PhpApiMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ParameterHydrator extends PhpApiMiddleware
{
    /**
     * @var Hydrator
     */
    private $hydrator;

    /**
     * MessageValidator constructor.
     * @param Hydrator $hydrator
     */
    public function __construct(Hydrator $hydrator)
    {
        $this->hydrator = $hydrator;
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
                $this->hydrator->hydrate(
                    $request->getAttribute($name),
                    $this->getOperation($request)->getParameter($name)->getSchema()
                )
            );
        }

        return $delegate->process($request);
    }
}
