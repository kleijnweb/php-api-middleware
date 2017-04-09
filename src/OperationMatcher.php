<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use KleijnWeb\PhpApi\Descriptions\Description\Description;
use KleijnWeb\PhpApi\Descriptions\Description\Parameter;
use KleijnWeb\PhpApi\Descriptions\Description\Repository;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Middleware\Util\Meta;
use KleijnWeb\PhpApi\Middleware\Util\PhpApiMiddleware;
use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OperationMatcher extends PhpApiMiddleware
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
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
        $uriPath = $request->getUri()->getPath();

        /** @var Description $description */
        foreach ($this->repository as $description) {

            $basePath = $description->getBasePath() ? "{$description->getBasePath()}/" : "";

            foreach ($description->getPaths() as $path) {
                $pathPattern = "$basePath{$path->getPath()}";

                $parameterNames = [];
                foreach ($path->getOperations() as $operation) {
                    foreach ($operation->getParameters() as $parameter) {
                        if ($parameter->getIn() === Parameter::IN_PATH
                            && ($schema = $parameter->getSchema()) instanceof ScalarSchema
                        ) {
                            $parameterName    = $parameter->getName();
                            $parameterNames[] = $parameterName;
                            $parameterPattern = "(?P<$parameterName>.*)(?=(/|$))";
                            $typePattern = null;
                            switch ($type = $schema->getType()) {
                                case Schema::TYPE_INT:
                                    $typePattern = '\d+';
                                    break;
                                case Schema::TYPE_NUMBER:
                                    $typePattern = '\d+(\.\d+)?';
                                    break;
                                case Schema::TYPE_NULL:
                                    $typePattern = 'null';
                                    break;
                                case Schema::TYPE_STRING:
                                    /** @var $schema ScalarSchema $routeString */
                                    if ($pattern = $schema->getPattern()) {
                                        $typePattern = $pattern;
                                    } elseif ($enum = $schema->getEnum()) {
                                        $typePattern = '('.implode('|', $enum).')';
                                    }
                                    break;
                                default:
                                    $typePattern = null;
                            }
                            if ($typePattern) {
                                $parameterPattern = str_replace(
                                    "<$parameterName>.*",
                                    "<$parameterName>$typePattern",
                                    $parameterPattern
                                );
                            }
                            $pathPattern = str_replace('{'.$parameterName.'}', $parameterPattern, $pathPattern);
                        }
                    }

                    if (preg_match("#^$pathPattern$#", $uriPath, $matches) > 0) {
                        if (strtolower($request->getMethod()) !== $operation->getMethod()) {
                            return Factory::createResponse(405);
                        }

                        $request = Meta::requestWith($request, $description, $operation, $path);

                        foreach ($parameterNames as $parameterName) {
                            $request = $request->withAttribute($parameterName, $matches[$parameterName]);
                        }

                        return $delegate->process($request);
                    }
                }

            }
        }

        return Factory::createResponse(404);
    }
}