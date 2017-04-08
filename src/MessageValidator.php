<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use KleijnWeb\PhpApi\Descriptions\MessageValidator as DescriptionMessageValidator;
use KleijnWeb\PhpApi\Middleware\Body\BodySerializer;
use KleijnWeb\PhpApi\Middleware\Util\PhpApiMiddleware;
use KleijnWeb\PhpApi\Descriptions\Request\RequestParameterAssembler;
use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MessageValidator extends PhpApiMiddleware
{
    /**
     * @var RequestParameterAssembler
     */
    private $parameterAssembler;

    /**
     * @var BodySerializer
     */
    private $bodySerializer;

    /**
     * @param RequestParameterAssembler $parameterAssembler
     * @param BodySerializer            $bodySerializer
     */
    public function __construct(RequestParameterAssembler $parameterAssembler, BodySerializer $bodySerializer)
    {
        $this->parameterAssembler = $parameterAssembler;
        $this->bodySerializer     = $bodySerializer;
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
        $validator = (new DescriptionMessageValidator($this->getDescription($request), $this->parameterAssembler));

        $result = $validator->validateRequest($request, $this->getPath($request)->getPath());

        if (!$result->isValid()) {
            return Factory::createResponse(400)
                ->withBody(
                    $this->createStringStream(
                        $this->bodySerializer->serialize(['errors' => $result->getErrorMessages()])
                    )
                )
                ->withHeader('Content-Type', $this->bodySerializer->getContentType());
        }

        return $delegate->process($request);
    }
}