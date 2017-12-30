<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CommandDispatcher extends PhpApiMiddleware
{
    const ATTRIBUTE = 'command.result';

    /**
     * @var callable[]
     */
    private $commands;

    /**
     * @param array $commands
     */
    public function __construct(array $commands)
    {
        foreach ($commands as $operationId => $command) {
            $this->addCommand($operationId, $command);
        }
    }

    /**
     * @param string   $operationId
     * @param callable $command
     * @return CommandDispatcher
     */
    public function addCommand(string $operationId, callable $command): CommandDispatcher
    {
        $this->commands[$operationId] = $command;

        return $this;
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

        $arguments = [];
        foreach ($operation->getParameters() as $parameter) {
            $arguments[] = $request->getAttribute($parameter->getName());
        }

        $result = call_user_func_array($this->commands[$operation->getId()], $arguments);

        return $delegate->process($request->withAttribute(self::ATTRIBUTE, $result));
    }
}
