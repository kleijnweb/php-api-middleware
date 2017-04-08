<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use KleijnWeb\PhpApi\Middleware\Body\BodyParser;
use KleijnWeb\PhpApi\Middleware\Util\PhpApiMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BodyParsing extends PhpApiMiddleware
{
    /**
     * @var BodyParser
     */
    private $bodyParser;

    /**
     * @param BodyParser $bodyParser
     */
    public function __construct(BodyParser $bodyParser)
    {
        $this->bodyParser = $bodyParser;
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
        $body = $request->getBody();

        if ($contents = $body->getContents()) {
            $body->rewind();
            $request = $request->withParsedBody($this->bodyParser->parse($contents));
        }

        return $delegate->process($request);
    }
}