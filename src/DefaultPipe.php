<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Middleware;

use Equip\Dispatch\MiddlewarePipe;
use Interop\Http\ServerMiddleware\DelegateInterface;
use KleijnWeb\PhpApi\Descriptions\Description\Repository;
use KleijnWeb\PhpApi\Descriptions\Hydrator\ClassNameResolver;
use KleijnWeb\PhpApi\Descriptions\Hydrator\ProcessorBuilder;
use KleijnWeb\PhpApi\Descriptions\Request\RequestParameterAssembler;
use KleijnWeb\PhpApi\Middleware\Body\JsonBodyParser;
use KleijnWeb\PhpApi\Middleware\Body\JsonBodySerializer;
use Psr\Http\Message\ServerRequestInterface;

class DefaultPipe extends MiddlewarePipe
{
    /**
     * @var ResultSerializer
     */
    private $resultSerializer;

    /**
     * @var bool
     */
    private $responding;

    /**
     * @param Repository $repository
     * @param array      $commands
     * @param array      $complexTypeNs
     * @param bool       $responding
     */
    public function __construct(
        Repository $repository,
        array $commands,
        array $complexTypeNs = [],
        bool $responding = true
    )
    {
        $assembler        = new RequestParameterAssembler();
        $jsonParser       = new JsonBodyParser();
        $jsonSerializer   = new JsonBodySerializer();
        $processorBuilder = new ProcessorBuilder(new ClassNameResolver($complexTypeNs));

        $this->resultSerializer = new ResultSerializer($jsonSerializer);

        $default = [
            new OperationMatcher($repository),
            new BodyParsing($jsonParser),
            new ParameterAssembler($assembler),
            new MessageValidator($assembler, $jsonSerializer),
            new ParameterHydrator($processorBuilder),
            new CommandDispatcher($commands),
            new ResponseBodyDehydrator($processorBuilder)
        ];

        parent::__construct($default);

        $this->responding = $responding;
    }

    /**
     * @return boolean
     */
    public function isResponding(): bool
    {
        return $this->responding;
    }

    /**
     * @param boolean $responding
     * @return DefaultPipe
     */
    public function setResponding(bool $responding): DefaultPipe
    {
        $this->responding = $responding;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dispatch(ServerRequestInterface $request, callable $default)
    {
        $this->appendResponderIfResponding();

        return parent::dispatch($request, $default);
    }

    /**
     * @inheritdoc
     */
    public function process(ServerRequestInterface $request, DelegateInterface $nextContainerDelegate)
    {
        $this->appendResponderIfResponding();

        return parent::process($request, $nextContainerDelegate);
    }

    private function appendResponderIfResponding()
    {
        if ($this->isResponding()) {
            $this->append($this->resultSerializer);
        }
    }
}
